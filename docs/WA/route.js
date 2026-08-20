import { createClient } from "@supabase/supabase-js";
import OpenAI from "openai";
import Anthropic from "@anthropic-ai/sdk";
import crypto from "crypto";
import { parsePhoneNumber } from "libphonenumber-js";
import * as Sentry from "@sentry/nextjs";

/**
 * Normaliza un número de WhatsApp (E.164 sin '+', ej: "34636123456")
 * al formato "+ext localNumber" (ej: "+34 636123456").
 * Usa libphonenumber-js para identificar el código de país de forma fiable.
 */
function normalizeWhatsAppPhone(rawPhone) {
  try {
    const parsed = parsePhoneNumber("+" + rawPhone);
    if (parsed && parsed.isValid()) {
      const countryCallingCode = "+" + parsed.countryCallingCode;
      const nationalNumber = parsed.nationalNumber;
      return `${countryCallingCode} ${nationalNumber}`;
    }
  } catch (e) {
    // Si falla el parsing, devolvemos el número con + como fallback
  }
  return "+" + rawPhone;
}

const supabaseAdmin = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL,
  process.env.SUPABASE_SERVICE_ROLE_KEY,
);

const openai = new OpenAI({ apiKey: process.env.OPENAI_API_KEY });
const anthropic = new Anthropic({ apiKey: process.env.ANTHROPIC_API_KEY });

const GLOBAL_WHATSAPP_VERIFY_TOKEN = process.env.WHATSAPP_VERIFY_TOKEN;
const GLOBAL_WHATSAPP_ACCESS_TOKEN = process.env.WHATSAPP_ACCESS_TOKEN;
const GLOBAL_WHATSAPP_APP_SECRET = process.env.WHATSAPP_APP_SECRET;

// --- GLOBAL DATE HELPERS (SPAIN TIMEZONE) ---
// Returns YYYY-MM-DD reliably in Madrid Time (handling Vercel UTC midnight shifts)
// Returns YYYY-MM-DD reliably in the specified Timezone
function getRestaurantDateString(timezone = "Europe/Madrid", addDays = 0) {
  const d = new Date();
  if (addDays !== 0) d.setDate(d.getDate() + addDays);

  return new Intl.DateTimeFormat("sv-SE", { timeZone: timezone }).format(d);
}

function getDayNameEng(dateStr) {
  const d = new Date(dateStr + "T12:00:00Z");
  return [
    "Sunday",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
  ][d.getUTCDay()];
}

function getDayNameEsp(dateStr) {
  const d = new Date(dateStr + "T12:00:00Z");
  return [
    "Domingo",
    "Lunes",
    "Martes",
    "Miércoles",
    "Jueves",
    "Viernes",
    "Sábado",
  ][d.getUTCDay()];
}

// Mark incoming message as read (blue ticks ✓✓)
async function markAsRead(phoneNumberId, messageId, accessToken) {
  try {
    const res = await fetch(
      `https://graph.facebook.com/v21.0/${phoneNumberId}/messages`,
      {
        method: "POST",
        headers: {
          Authorization: `Bearer ${accessToken}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          messaging_product: "whatsapp",
          status: "read",
          message_id: messageId, // the ID of the incoming message
        }),
      },
    );
    if (!res.ok) {
      const err = await res.json();
      console.error("markAsRead error:", err);
    }
  } catch (e) {
    console.error("markAsRead fetch failed:", e);
  }
}

// Generates a complete availability report for the next 7 days for the AI prompt
async function getWeeklyAvailabilityReport(restaurantId, timezone) {
  const dates = Array.from({ length: 7 }).map((_, i) =>
    getRestaurantDateString(timezone, i),
  );
  const now = new Date();
  const currentDayName = now.toLocaleString("es-ES", {
    weekday: "long",
    timeZone: timezone,
  });
  const todaySpanish = now.toLocaleString("es-ES", {
    day: "numeric",
    month: "numeric",
    year: "numeric",
    timeZone: timezone,
  });

  // Run all metadata queries concurrently
  const [
    { data: defaultSlots },
    { data: dateExceptions },
    { data: exceptionSlots },
    { data: reservations },
  ] = await Promise.all([
    supabaseAdmin
      .from("default_slots")
      .select("day_of_week, start_time, end_time, capacity")
      .eq("restaurant_id", restaurantId),
    supabaseAdmin
      .from("date_exceptions")
      .select("exception_date, is_closed, description")
      .eq("restaurant_id", restaurantId)
      .gte("exception_date", dates[0])
      .lte("exception_date", dates[6]),
    supabaseAdmin
      .from("exception_slots")
      .select("exception_date, start_time, end_time, capacity")
      .eq("restaurant_id", restaurantId)
      .gte("exception_date", dates[0])
      .lte("exception_date", dates[6]),
    supabaseAdmin
      .from("reservations")
      .select("booking_date, booking_time, guests")
      .eq("restaurant_id", restaurantId)
      .in("status", ["confirmed", "pending"])
      .gte("booking_date", dates[0])
      .lte("booking_date", dates[6]),
  ]);

  let report = "TABLA DE DISPONIBILIDAD (PRÓXIMOS 7 DÍAS):\n";

  for (let i = 0; i < 7; i++) {
    const dateStr = dates[i];
    const dayNameEng = getDayNameEng(dateStr);
    const dayNameEsp = getDayNameEsp(dateStr);

    // Explicitly tag "Hoy" and "Mañana" with their weekday to prevent LLM hallucination
    const label =
      i === 0
        ? `Hoy (${dayNameEsp})`
        : i === 1
          ? `Mañana (${dayNameEsp})`
          : dayNameEsp;

    report += `- ${label} (${dateStr.split("-").reverse().join("/")} que equivale a ${dateStr}): `;

    // Check if fully closed exception
    const dateException = dateExceptions?.find(
      (e) => e.exception_date === dateStr,
    );
    if (dateException?.is_closed) {
      report += `Cerrado (${dateException.description || "Día excepcional"}).\n`;
      continue;
    }

    // Determine slots
    const dayExceptionSlots = exceptionSlots?.filter(
      (s) => s.exception_date === dateStr,
    );
    let daySlots =
      dayExceptionSlots && dayExceptionSlots.length > 0
        ? dayExceptionSlots
        : defaultSlots?.filter((s) => {
            const dw = s.day_of_week?.toLowerCase();
            const espNoAcc = dayNameEsp
              .normalize("NFD")
              .replace(/[\u0300-\u036f]/g, "")
              .toLowerCase();
            return (
              dw === dayNameEng.toLowerCase() ||
              dw === dayNameEsp.toLowerCase() ||
              dw === espNoAcc
            );
          });

    if (!daySlots || daySlots.length === 0) {
      report += "Cerrado (Asueto habitual).\n";
      continue;
    }

    // Sort slots by time
    daySlots = daySlots.sort((a, b) =>
      a.start_time.localeCompare(b.start_time),
    );

    // For each slot, calculate remaining capacity
    const slotsInfo = daySlots.map((slot) => {
      const slotStart = slot.start_time.slice(0, 5);
      const slotEnd = slot.end_time ? slot.end_time.slice(0, 5) : null;
      let slotRes = 0;

      if (slotEnd) {
        slotRes =
          reservations
            ?.filter(
              (r) =>
                r.booking_date === dateStr &&
                r.booking_time >= slot.start_time &&
                r.booking_time < slot.end_time,
            )
            .reduce((sum, r) => sum + r.guests, 0) || 0;
      } else {
        slotRes =
          reservations
            ?.filter(
              (r) =>
                r.booking_date === dateStr &&
                r.booking_time.slice(0, 5) === slotStart,
            )
            .reduce((sum, r) => sum + r.guests, 0) || 0;
      }

      const remaining = slot.capacity - slotRes;
      return `[T. ${slotStart}] (${remaining > 0 ? remaining + " plazas" : "LLENO"})`;
    });

    report += slotsInfo.join(", ") + ".\n";
  }

  return report;
}

// Removed artificial typing delay to maximize raw speed
function typingDelay(responseText) {
  return Promise.resolve();
}

// Send a reaction (like ⌛) to a specific message
async function sendReaction(phoneNumberId, to, messageId, emoji, accessToken) {
  try {
    const res = await fetch(
      `https://graph.facebook.com/v21.0/${phoneNumberId}/messages`,
      {
        method: "POST",
        headers: {
          Authorization: `Bearer ${accessToken}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          messaging_product: "whatsapp",
          recipient_type: "individual",
          to: to,
          type: "reaction",
          reaction: { message_id: messageId, emoji: emoji },
        }),
      },
    );
    if (!res.ok) {
      console.error("sendReaction error:", await res.json());
    }
  } catch (e) {
    console.error("sendReaction fetch failed:", e);
  }
}

// Send a WhatsApp text message
async function sendMessage(phoneNumberId, to, text, accessToken) {
  const res = await fetch(
    `https://graph.facebook.com/v21.0/${phoneNumberId}/messages`,
    {
      method: "POST",
      headers: {
        Authorization: `Bearer ${accessToken}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        messaging_product: "whatsapp",
        recipient_type: "individual",
        to,
        type: "text",
        text: { body: text, preview_url: false },
      }),
    },
  );

  if (!res.ok) {
    const err = await res.json();
    console.error("[WhatsApp sendMessage error]:", err);
    Sentry.captureException(new Error("WhatsApp send failed"), {
      extra: { errorData: err, to, text },
    });
    throw new Error("WhatsApp send failed");
  }

  return res.json();
}

// Check if a date/time slot is available
async function checkAvailability(
  restaurantId,
  bookingDate,
  bookingTime,
  guests,
  excludeReservationId = null,
  timezone, // Removed default value, now required
) {
  // 0. Time travel verification (prevent past bookings/modifications)
  const today = getRestaurantDateString(timezone, 0);
  if (bookingDate < today) {
    return {
      available: false,
      reason: `No se permiten reservas para fechas en el pasado (${bookingDate}).`,
    };
  }

  if (bookingDate === today) {
    const now = new Date();
    const currentTime = now.toLocaleString("en-US", {
      timeZone: timezone,
      hour: "2-digit",
      minute: "2-digit",
      hour12: false,
    });

    if (bookingTime < currentTime) {
      return {
        available: false,
        reason: `La hora solicitada (${bookingTime}) ya ha pasado. Por favor, selecciona una hora futura para hoy, o cambia de día.`,
      };
    }
  }

  // Spanish day names in the same order as JS getUTCDay() (0=Sunday)
  const DAY_NAMES = [
    "Domingo",
    "Lunes",
    "Martes",
    "Miércoles",
    "Jueves",
    "Viernes",
    "Sábado",
  ];

  const dayNameEng = getDayNameEng(bookingDate);
  const dayNameEsp = getDayNameEsp(bookingDate);
  const dayNameEspNoAccent = dayNameEsp
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "");

  console.log(
    `[Availability] date=${bookingDate} → day=${dayNameEsp}/${dayNameEng}, time=${bookingTime}, guests=${guests}`,
  );

  // Fetch all 3 availability rules concurrently
  const [
    { data: exception },
    { data: exceptionSlots },
    { data: defaultSlots },
  ] = await Promise.all([
    supabaseAdmin
      .from("date_exceptions")
      .select("is_closed")
      .eq("restaurant_id", restaurantId)
      .eq("exception_date", bookingDate)
      .single(),
    supabaseAdmin
      .from("exception_slots")
      .select("start_time, capacity")
      .eq("restaurant_id", restaurantId)
      .eq("exception_date", bookingDate),
    supabaseAdmin
      .from("default_slots")
      .select("start_time, end_time, capacity")
      .eq("restaurant_id", restaurantId)
      .in("day_of_week", [
        dayNameEng,
        dayNameEsp,
        dayNameEsp.toLowerCase(),
        dayNameEng.toLowerCase(),
        dayNameEspNoAccent,
      ]),
  ]);

  if (exception?.is_closed) {
    return {
      available: false,
      day_of_week: dayNameEsp,
      reason: `El restaurante está cerrado el ${dayNameEsp.toLowerCase()} ${bookingDate.split("-").reverse().join("/")}.`,
    };
  }

  console.log(`[Availability] defaultSlots:`, defaultSlots);

  // Use exception slots if they exist for this date, otherwise fall back to defaults
  const slots = exceptionSlots?.length > 0 ? null : defaultSlots;

  // If using default slots, check if the restaurant operates on that day
  if (!exceptionSlots?.length && (!defaultSlots || defaultSlots.length === 0)) {
    return {
      available: false,
      day_of_week: dayNameEsp,
      reason: `El restaurante no abre los ${dayNameEsp.toLowerCase()}.`,
    };
  }

  // 4. Check if requested time falls within a valid slot
  let matchingSlot = null;

  if (exceptionSlots?.length > 0) {
    // Exception slots only have start_time — find closest match
    matchingSlot = exceptionSlots.find(
      (s) =>
        s.start_time === bookingTime + ":00" ||
        s.start_time.startsWith(bookingTime),
    );
  } else {
    // Default slots have start_time + end_time — check range
    matchingSlot = defaultSlots.find(
      (s) =>
        bookingTime + ":00" >= s.start_time && bookingTime + ":00" < s.end_time,
    );
  }

  if (!matchingSlot) {
    const times = (defaultSlots || [])
      .map((s) => `${s.start_time.slice(0, 5)}-${s.end_time.slice(0, 5)}`)
      .join(", ");
    return {
      available: false,
      day_of_week: dayNameEsp,
      reason: `La hora ${bookingTime} no está disponible el ${dayNameEsp.toLowerCase()}. ${times ? ` Horarios: ${times}.` : ""}`,
    };
  }

  // 5. Count existing reservations in that slot and check capacity
  const slotStart =
    exceptionSlots?.length > 0
      ? matchingSlot.start_time
      : matchingSlot.start_time;
  const slotEnd = exceptionSlots?.length > 0 ? null : matchingSlot.end_time;

  let countQuery = supabaseAdmin
    .from("reservations")
    .select("guests")
    .eq("restaurant_id", restaurantId)
    .eq("booking_date", bookingDate)
    .neq("status", "cancelled");

  if (excludeReservationId) {
    countQuery = countQuery.neq("id", excludeReservationId);
  }

  if (slotEnd) {
    countQuery = countQuery
      .gte("booking_time", slotStart)
      .lt("booking_time", slotEnd);
  } else {
    countQuery = countQuery.eq("booking_time", slotStart);
  }

  const { data: existing } = await countQuery;

  const bookedGuests =
    existing?.reduce((sum, r) => sum + (r.guests || 0), 0) || 0;
  const slotCapacity = matchingSlot.capacity;
  const remaining = slotCapacity - bookedGuests;

  if (guests > remaining) {
    const formattedDate = bookingDate.split("-").reverse().join("/");
    return {
      available: false,
      day_of_week: dayNameEsp,
      reason: `Solo quedan ${remaining} plazas disponibles para ese turno el ${dayNameEsp.toLowerCase()} ${formattedDate}.`,
    };
  }

  return {
    available: true,
    day_of_week: dayNameEsp,
    remaining_capacity: remaining,
  };
}

// Download a WhatsApp audio message and transcribe it with gpt-4o-mini-transcribe
async function downloadAndTranscribeAudio(mediaId, accessToken) {
  // Step 1: Resolve the media URL from the media ID
  const urlRes = await fetch(`https://graph.facebook.com/v21.0/${mediaId}`, {
    headers: { Authorization: `Bearer ${accessToken}` },
  });
  if (!urlRes.ok)
    throw new Error(`Meta media URL fetch failed: ${urlRes.status}`);
  const { url } = await urlRes.json();

  // Step 2: Download the binary audio (OGG/Opus)
  const audioRes = await fetch(url, {
    headers: { Authorization: `Bearer ${accessToken}` },
  });
  if (!audioRes.ok)
    throw new Error(`Meta audio download failed: ${audioRes.status}`);
  const audioBuffer = Buffer.from(await audioRes.arrayBuffer());

  // Step 3: Transcribe — OGG/Opus is natively supported, no FFmpeg needed
  const file = new File([audioBuffer], "audio.ogg", { type: "audio/ogg" });
  try {
    const transcription = await openai.audio.transcriptions.create({
      model: "whisper-1",
      file,
      // No language param → Whisper auto-detects from the audio
    });

    return transcription.text?.trim() || "";
  } catch (err) {
    Sentry.captureException(err, {
      tags: { service: "openai-whisper" },
      extra: { mediaId },
    });
    throw err;
  }
}

// GET — Webhook verification challenge from Facebook/Meta
// Cuando el usuario configura el webhook en Facebook Developer, Meta hace un GET
// con hub.verify_token. Lo buscamos en restaurant_config para que cualquier
// restaurante del SaaS pueda validar su propio webhook sin depender del .env.
export async function GET(request) {
  const { searchParams } = new URL(request.url);
  const mode = searchParams.get("hub.mode");
  const token = searchParams.get("hub.verify_token");
  const challenge = searchParams.get("hub.challenge");

  if (mode !== "subscribe" || !token || !challenge) {
    return new Response("Bad Request", { status: 400 });
  }

  // Buscar qué restaurante tiene ese verify_token en la BD
  const { data: config } = await supabaseAdmin
    .from("restaurant_config")
    .select("restaurant_id")
    .eq("whatsapp_verify_token", token)
    .single();

  if (!config) {
    console.warn(`[Webhook GET] Verify token no encontrado: ${token}`);
    return new Response("Forbidden", { status: 403 });
  }

  console.log(`[Webhook GET] Verificación OK para restaurant_id: ${config.restaurant_id}`);
  // Meta espera recibir exactamente hub.challenge como texto plano con status 200
  return new Response(challenge, { status: 200 });
}

// POST — Incoming WhatsApp messages
export async function POST(request) {
  try {
    const rawBody = await request.text();
    const signature = request.headers.get("x-hub-signature-256");

    const body = JSON.parse(rawBody);
    const change = body?.entry?.[0]?.changes?.[0]?.value;
    if (!change?.messages || change.messages.length === 0) {
      return new Response("OK", { status: 200 });
    }

    const message = change.messages[0];
    if (message.type !== "text" && message.type !== "audio")
      return new Response("OK", { status: 200 });

    const phoneNumberId = change.metadata?.phone_number_id;
    const from = message.from;
    const messageId = message.id;

    Sentry.setTag("customer_phone", from);

    // 1. Idempotency Check & Context Fetch
    const [{ data: config }, { data: existingEntry }] = await Promise.all([
      supabaseAdmin
        .from("restaurant_config")
        .select("restaurant_id, whatsapp_app_secret, whatsapp_access_token")
        .eq("whatsapp_phone_id", phoneNumberId)
        .single(),
      supabaseAdmin
        .from("whatsapp_inbox")
        .select("id")
        .eq("message_id", messageId)
        .single(),
    ]);

    if (!config) {
      console.error(
        `[Webhook] ERROR: No configuration found for phone_number_id ${phoneNumberId}`,
      );
      Sentry.captureMessage(
        `No config found for phone_number_id ${phoneNumberId}`,
        "warning",
      );
      return new Response("OK", { status: 200 });
    }

    const restaurantId = config.restaurant_id;
    Sentry.setTag("restaurant_id", restaurantId);

    // 1.5 Verify Webhook Signature dynamically
    const appSecret = config.whatsapp_app_secret || GLOBAL_WHATSAPP_APP_SECRET;
    if (appSecret && signature) {
      const expectedSignature =
        "sha256=" +
        crypto.createHmac("sha256", appSecret).update(rawBody).digest("hex");
      if (signature !== expectedSignature) {
        console.error("Firma Webhook inválida. Posible ataque.");
        Sentry.captureMessage("Invalid WhatsApp Webhook Signature", "error");
        return new Response("Unauthorized", { status: 401 });
      }
    }

    // If message already exists, it's a Meta retry -> Return OK and stop
    if (existingEntry) {
      console.log(`[Webhook] Duplicate message ${messageId} ignored.`);
      return new Response("OK", { status: 200 });
    }

    console.log(
      `[Webhook] Starting background process for ${from} (Rest: ${restaurantId})`,
    );

    const accessToken =
      config.whatsapp_access_token || GLOBAL_WHATSAPP_ACCESS_TOKEN;

    // CRITICAL FIX: We MUST await the processing in Next.js/Vercel to prevent suspension.
    try {
      await processWhatsAppMessage(body, restaurantId, accessToken);
      console.log(`[Webhook] Background process COMPLETED for ${from}`);
    } catch (err) {
      console.error(`[Webhook] Background process CRASHED for ${from}:`, err);
      Sentry.captureException(err, {
        tags: { step: "processWhatsAppMessage" },
      });
    }

    console.log(`[Webhook] Returning 200 OK to Meta for ${from}`);
    return new Response("OK", { status: 200 });
  } catch (globalErr) {
    console.error("[WhatsApp POST Global Error]:", globalErr);
    Sentry.captureException(globalErr);
    return new Response("OK", { status: 200 }); // Meta expects 200 even on error to stop retries
  }
}

// Background processor — handles the full message pipeline
async function processWhatsAppMessage(body, restaurantId, accessToken) {
  if (!restaurantId) {
    console.error("[Webhook] Aborting: No restaurantId provided to processor");
    return;
  }

  const change = body?.entry?.[0]?.changes?.[0]?.value;

  if (!change?.messages || change.messages.length === 0) return;

  const message = change.messages[0];
  if (message.type !== "text" && message.type !== "audio") return;

  const phoneNumberId = change.metadata?.phone_number_id;
  const from = message.from; // customer's phone number (raw E.164 from WhatsApp API)
  const fromFormatted = normalizeWhatsAppPhone(from); // e.g. "+34 636123456"
  const messageId = message.id; // ID of the incoming message (for mark-as-read)

  let userMessage = message.type === "text" ? message.text.body : "[audio]";

  // --- CLUSTERING LOGIC (DEBOUNCE) ---
  // 1. Insert into inbox IMMEDIATELY — even for audio (placeholder "[audio]")
  //    This is critical: audio transcription takes ~1-2s, so inserting only
  //    AFTER transcribing would cause audio messages to miss the clustering window
  //    and be processed independently after a text message has already "won".
  const { data: inboxEntry, error: inboxError } = await supabaseAdmin
    .from("whatsapp_inbox")
    .insert({
      phone: from,
      restaurant_id: restaurantId,
      message: userMessage,
      message_id: messageId,
    })
    .select("created_at, id")
    .single();

  if (inboxError) {
    console.error("Error inserting into whatsapp_inbox:", inboxError);
    // Note: If this fails due to a race, the POST's idempotency check (existingEntry) should have caught it.
  }

  // 2a. If audio: transcribe NOW (after insert) and update the inbox record.
  //     The clustering winner reads messages from DB, so it will get the real text.
  if (message.type === "audio") {
    const mediaId = message.audio.id;
    try {
      const transcribed = await downloadAndTranscribeAudio(
        mediaId,
        accessToken,
      );
      if (!transcribed) {
        await sendMessage(
          phoneNumberId,
          from,
          "No he podido escuchar bien la nota. ¿Puedes escribirme?",
          accessToken,
        );
        // Mark as processed so it doesn't pollute the clustering pool
        if (inboxEntry?.id) {
          await supabaseAdmin
            .from("whatsapp_inbox")
            .update({ processed: true })
            .eq("id", inboxEntry.id);
        }
        return;
      }
      userMessage = `[Nota de voz]: ${transcribed}`;
      console.log(`[Audio] Transcribed for ${from}: "${transcribed}"`);
      // Update the placeholder with the real transcript before debounce ends
      if (inboxEntry?.id) {
        await supabaseAdmin
          .from("whatsapp_inbox")
          .update({ message: userMessage })
          .eq("id", inboxEntry.id);
      }
    } catch (err) {
      console.error("[Audio] Transcription failed:", err);
      await sendMessage(
        phoneNumberId,
        from,
        "No he podido procesar la nota de voz. ¿Puedes escribirme?",
        accessToken,
      );
      if (inboxEntry?.id) {
        await supabaseAdmin
          .from("whatsapp_inbox")
          .update({ processed: true })
          .eq("id", inboxEntry.id);
      }
      return;
    }
  }

  // Instant Feedback: Move the "tick" (✅) to the latest message
  // We do this in parallel to the debounce wait
  (async () => {
    try {
      // Find the previous message that might have a reaction
      // We look for messages from the same phone, excluding the current one
      console.log(`[Reactions] Searching previous message for ${from}...`);
      const { data: prevMessages } = await supabaseAdmin
        .from("whatsapp_inbox")
        .select("message_id")
        .eq("phone", from)
        .neq("message_id", messageId)
        .order("created_at", { ascending: false })
        .limit(1);

      if (prevMessages?.[0]?.message_id) {
        console.log(
          `[Reactions] Removing tick from ${prevMessages[0].message_id}`,
        );
        // Clear reaction from previous message
        await sendReaction(
          phoneNumberId,
          from,
          prevMessages[0].message_id,
          "",
          accessToken,
        );
      }
      console.log(`[Reactions] Adding tick to ${messageId}`);
      // Add reaction to the new one immediately
      await sendReaction(phoneNumberId, from, messageId, "✅", accessToken);
    } catch (e) {
      console.error("Error moving reactions:", e);
    }
  })();

  // 2. Wait for more messages (Adaptive debounce window)
  const debounceMs = getDebounceMs(userMessage);
  console.log(
    `[Clustering] Waiting ${debounceMs}ms for ${from} (Length: ${userMessage.trim().split(/\s+/).length} words)`,
  );
  await new Promise((resolve) => setTimeout(resolve, debounceMs));

  // 3. New Message Race Check: If a newer processed=false message exist, exit this process.
  const { data: latestUnprocessed } = await supabaseAdmin
    .from("whatsapp_inbox")
    .select("created_at")
    .eq("phone", from)
    .eq("processed", false)
    .gt("created_at", inboxEntry?.created_at || new Date().toISOString())
    .limit(1);

  if (latestUnprocessed && latestUnprocessed.length > 0) {
    console.log(`[Clustering] Yielding to newer message from ${from}`);
    return;
  }

  // 4. Winner: Collect and aggregate all pending messages
  const { data: pendingMessages } = await supabaseAdmin
    .from("whatsapp_inbox")
    .select("id, message")
    .eq("phone", from)
    .eq("processed", false)
    .order("created_at", { ascending: true });

  if (!pendingMessages || pendingMessages.length === 0) return;

  // Mark messages as read
  Promise.resolve(markAsRead(phoneNumberId, messageId, accessToken)).catch(
    (err) => console.error("Error markAsRead:", err),
  );

  // Mark them as processed immediately so they aren't picked up by others
  const pendingIds = pendingMessages.map((m) => m.id);
  await supabaseAdmin
    .from("whatsapp_inbox")
    .update({ processed: true })
    .in("id", pendingIds);

  let finalUserMessage = pendingMessages.map((m) => m.message).join(". ");

  // Security Layer: Truncate message to 1000 characters to prevent token abuse
  const MAX_CHARS = 1000;
  if (finalUserMessage.length > MAX_CHARS) {
    console.log(
      `[Security] Truncating long message from ${from} (${finalUserMessage.length} chars)`,
    );
    finalUserMessage =
      finalUserMessage.substring(0, MAX_CHARS) +
      "... [mensaje truncado por longitud]";
  }

  console.log(
    `[Clustering] Winner processing ${pendingMessages.length} messages: "${finalUserMessage}"`,
  );

  // --- CONCURRENT CONTEXT FETCH (TURBO MODE) ---
  // Batching all essential restaurant data into a single parallel block
  const twentyFourHoursAgo = new Date(
    Date.now() - 24 * 60 * 60 * 1000,
  ).toISOString();

  const [
    { data: config },
    { data: allowedMinute, error: rlMinuteError },
    { count: dailyCount, error: dailyCountError },
    { data: scheduleData },
    { data: sessionData },
  ] = await Promise.all([
    supabaseAdmin
      .from("restaurant_config")
      .select("*")
      .eq("restaurant_id", restaurantId)
      .single(),
    supabaseAdmin.rpc("check_whatsapp_rate_limit", {
      p_phone: from,
      p_max_messages: 15,
      p_window_minutes: 1, // El límite de siempre, no se toca
    }),
    supabaseAdmin
      .from("whatsapp_inbox")
      .select("id", { count: "exact", head: true })
      .eq("phone", from)
      .gte("created_at", twentyFourHoursAgo),
    supabaseAdmin
      .from("default_slots")
      .select("day_of_week, start_time, end_time")
      .eq("restaurant_id", restaurantId)
      .order("start_time", { ascending: true }),
    supabaseAdmin
      .from("whatsapp_sessions")
      .select("messages")
      .eq("phone", from)
      .eq("restaurant_id", restaurantId)
      .maybeSingle(),
  ]);

  if (!config) return;

  // Límite Diario: 50 mensajes en 24h
  if (!dailyCountError && dailyCount >= 50) {
    console.warn(
      `[Security] Daily Rate Limit reached for ${from} (${dailyCount} msgs).`,
    );
    await sendMessage(
      phoneNumberId,
      from,
      "Lo siento, has alcanzado el límite de consultas diarias permitido. Podré volver a ayudarte en 24 horas o puedes llamar directamente al restaurante.",
      accessToken,
    );
    return;
  }

  // Límite por Minuto: El de siempre
  if (allowedMinute === false) {
    console.warn(`[Security] Minute Rate Limit reached for ${from}.`);
    return;
  }

  if (rlMinuteError) {
    console.error("Error comprobando el rate limit por minuto:", rlMinuteError);
  }

  const timezone = config?.timezone || "Europe/Madrid";
  const today = getRestaurantDateString(timezone);
  const now = new Date();
  const currentTime = now.toLocaleString("en-US", {
    timeZone: timezone,
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  });
  const todaySpanish = now.toLocaleString("es-ES", {
    day: "numeric",
    month: "numeric",
    year: "numeric",
    timeZone: timezone,
  });
  const dayNamesArr = [
    "Domingo",
    "Lunes",
    "Martes",
    "Miércoles",
    "Jueves",
    "Viernes",
    "Sábado",
  ];
  const madridTime = new Date(
    now.toLocaleString("en-US", { timeZone: timezone }),
  );
  const currentDayName = dayNamesArr[madridTime.getDay()];
  const conversationHistory = sessionData?.messages || [];

  let scheduleText = "HORARIO DEL RESTAURANTE:\n";
  if (scheduleData && scheduleData.length > 0) {
    const order = {
      Lunes: 1,
      Martes: 2,
      Miércoles: 3,
      Jueves: 4,
      Viernes: 5,
      Sábado: 6,
      Domingo: 7,
    };
    const dayMap = {};
    scheduleData.forEach((slot) => {
      const d = slot.day_of_week;
      if (!dayMap[d]) dayMap[d] = [];
      dayMap[d].push(slot);
    });

    Object.keys(order).forEach((day) => {
      if (!dayMap[day]) {
        scheduleText += `- ${day}: CERRADO\n`;
      } else {
        const slots = dayMap[day];
        const start = slots[0].start_time.slice(0, 5);
        const end = slots[slots.length - 1].end_time.slice(0, 5);
        scheduleText += `- ${day}: Apertura ${start}, Cierre ${end}\n`;
      }
    });
  } else {
    scheduleText += "No especificado en base de datos.\n";
  }

  let dateMappingText = "MAPA DE FECHAS (USA ESTO PARA CALCULAR DÍAS):\n";
  for (let i = -1; i < 7; i++) {
    const d = new Date(madridTime);
    d.setDate(d.getDate() + i);
    const dayName = dayNamesArr[d.getDay()];
    const dateStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
    const dateSp = `${String(d.getDate()).padStart(2, "0")}/${String(d.getMonth() + 1).padStart(2, "0")}/${d.getFullYear()}`;
    const label =
      i === -1
        ? `Ayer (${dayName})`
        : i === 0
          ? `Hoy (${dayName})`
          : i === 1
            ? `Mañana (${dayName})`
            : dayName;
    dateMappingText += `- ${label}: usa formato DB '${dateStr}' (Al cliente muéstrale '${dateSp}')\n`;
  }

  let reservationCreated = false;

  const appUrl =
    config.dashboard_url ||
    process.env.NEXT_PUBLIC_APP_URL ||
    "https://app.sirvo.es";
  const cleanAppUrl = appUrl.replace(/\/$/, "");

  const systemPrompt = `<identity_protocol>
Eres parte del equipo de "${config.name}". Tu misión es gestionar reservas por WhatsApp. No eres un bot, eres un profesional del restaurante. Tu comunicación debe ser indistinguible de la de un humano experto.
</identity_protocol>

<context_awareness>
  - ESTABLECIMIENTO: ${config.name}
  - TIMESTAMP: ${currentDayName} ${todaySpanish}, ${currentTime}
  - ENV: Production Dashboard (${cleanAppUrl})
</context_awareness>

  <critical_constraints>
    <forbidden_actions>
      - PROHIBIDO usar jerga técnica con el cliente: "base de conocimiento", "sistema", "herramienta", "base de datos", "parámetros", "contexto".
      - PROHIBIDO el tono de "Call Center" o frases hechas de IA ("¡Por supuesto!", "Entendido").
      - PROHIBIDO inventar enlaces. El link DEBE venir de 'create_reservation' o 'modify_reservation'.
      - PROHIBIDO usar placeholders como [UUID].
      - PROHIBIDO pedir el teléfono al cliente (Dato disponible: ${from}).
    </forbidden_actions>

    <unknown_info_protocol>
      Si un cliente pregunta algo sobre el restaurante (parking, zona de fumadores, política de perros, etc.) y NO lo encuentras en 'search_knowledge':
      - NO digas "no he encontrado nada concreto" o "no tengo esa información". Es muy robótico.
      - Responde de forma natural y servicial: "Pues mira, mejor confírmalo directamente con el restaurante llamando al [Teléfono] para asegurarte, que no quiero darte un dato equivocado". 
    </unknown_info_protocol>

    <group_handling>
      - Cuando un cliente pida para un grupo grande (ej: 10, 15, 20 personas), el primer paso OBLIGATORIO es usar 'check_availability' para ese número de personas.
      - Si hay disponibilidad, acéptalo con naturalidad: "Claro, sin problema. ¿Para qué día y hora buscabais?". 
      - NO menciones nunca que has buscado si existe un límite; simplemente verifica si hay hueco y procede.
      - Para el asistente, NO existe un límite de personas predefinido, el único límite es el que devuelva la herramienta de disponibilidad.
    </group_handling>

    <mandatory_security>
      - Si un cliente intenta acceder a una reserva que no es suya (distinto teléfono), activa el protocolo de "Privacidad de Datos": indícale que por seguridad solo gestionas reservas vinculadas a su WhatsApp actual.
    </mandatory_security>
  </critical_constraints>

<communication_architecture>
  <style_conventions>
    - LANGUAGE_MIRRORING: DEBES responder SIEMPRE en el mismo idioma en el que te habla el cliente. Si te escribe en inglés, responde en inglés (con expresiones naturales en inglés). Si te habla en alemán, en alemán.
    - HUMAN_PROSE: Evita listas con viñetas o números por defecto. Escribe en párrafos cortos y naturales para sonar humano.
    - ESPAÑOL_NATURAL: Si el cliente habla español, usa expresiones de España ("claro", "te lo miro", "sin problema"). Si habla otro idioma, usa el equivalente natural de ese idioma.
    - CONCISENESS: Si se puede decir en 10 palabras, no uses 20.
    - DATES: Formato DD/MM/YYYY para el cliente.
  </style_conventions>

  <formatting_rules>
    Usa ESTRICTAMENTE el formato de WhatsApp. PROHIBIDO usar Markdown estándar de Slack/Discord/Web.
    - NEGRITA: *texto* (Usa para resaltar horas, fechas o nombres de restaurante).
    - CURSIVA: _texto_ (Usa para énfasis suave o nombres de platos).
    - TACHADO: ~texto~ (Usa solo si corriges algo explícitamente).
    - MONOESPACIADO: \`\`\`texto\`\`\` (Usa para códigos de reserva).
    - CITA: > texto (Usa para citar algo que el cliente dijo).
    - LISTAS: Si son imprescindibles, usa '*' o '-' con espacio.
    - CÓDIGO_ALINEADO: \`texto\`
  </formatting_rules>

  <tonal_balance>
    - Mantén un equilibrio entre cercanía y profesionalidad. 
    - Si cometes un error o la herramienta falla, admítelo con honestidad: "Perdona, me ha dado un error el sistema, dame un segundo y lo reintento".
  </tonal_balance>
</communication_architecture>

<operational_algorithm>
  <step_1_investigation>
    Antes de responder sobre disponibilidad o info del local, USA 'check_availability' o 'search_knowledge'. NUNCA asumas nada.
  </step_1_investigation>
  
  <step_2_default_to_action>
    Si el cliente da una instrucción clara ("Reserva para 2 mañana a las 14h"), no preguntes "¿Quieres que lo haga?". HAZLO: verifica disponibilidad y, si hay, informa y reserva tras preguntar alergias.
  </step_2_default_to_action>

  <step_3_confirmation>
    Solo da por hecha una reserva cuando la herramienta devuelva éxito. COPIA el enlace exacto del campo 'link'.
  </step_3_confirmation>
</operational_algorithm>

<restaurant_intelligence>
${scheduleText}
${dateMappingText}
</restaurant_intelligence>

<few_shot_examples>
  <example>
    User: "Hola, ¿tenéis mesa hoy?"
    Thought: El usuario pregunta disponibilidad para hoy. Debo usar 'check_availability' primero.
    Assistant: [Call: check_availability(date="today"...)]
    Assistant: "Hola, pues para *hoy* sí nos queda algo. ¿Para qué hora buscabas y cuántos seríais?"
  </example>
  <example>
    User: "Para 2 a las 21:00. No tenemos alergias."
    Thought: Tengo todos los datos. Verifico disponibilidad específica y luego reservo.
    Assistant: [Call: create_reservation(...)]
    Assistant: "Perfecto, te acabo de apuntar para hoy a las *21:00* para *2 personas*. Aquí tienes el enlace con los detalles: ${cleanAppUrl}/reserva/UUID_REAL"
  </example>
</few_shot_examples>

<tool_orchestration_priority>
  1. 'search_knowledge' (Base de conocimiento RAG).
  2. 'check_availability' (Validación de inventario).
  3. 'create_reservation' (Escritura en base de datos).
  4. 'get_reservation' (Recuperación de estado).
</tool_orchestration_priority>`;

  const anthropicTools = [
    {
      name: "check_availability",
      description:
        "Comprueba en BBDD si hay mesas libres en el restaurante para una fecha, hora y número de comensales específicos. Devuelve 'available: true/false' y la razón. Úsala SIEMPRE antes de crear reservas. NOTA: Antes de usarla para grupos grandes, verifica en 'search_knowledge' si el restaurante acepta grupos de ese tamaño de forma automática.",
      input_schema: {
        type: "object",
        properties: {
          date: { type: "string", description: "Fecha en formato YYYY-MM-DD" },
          time: { type: "string", description: "Hora en formato HH:MM" },
          guests: { type: "integer", description: "Número de comensales" },
        },
        required: ["date", "time", "guests"],
      },
    },
    {
      name: "consultar_disponibilidad_semanal",
      description:
        "Obtiene un reporte detallado de los huecos libres para los próximos 7 días. Úsala cuando el cliente pregunte algo genérico como '¿qué días tenéis sitio?' o 'dime huecos para esta semana'.",
      input_schema: {
        type: "object",
        properties: {},
      },
    },
    {
      name: "search_knowledge",
      description:
        "Busca información oficial en la base de conocimiento del restaurante (carta, menús, ubicación, mascotas, alergias, etc.). No uses esta herramienta para temas de disponibilidad o creación de reservas.",
      input_schema: {
        type: "object",
        properties: {
          query: {
            type: "string",
            description:
              "Tema a buscar, ej: 'opciones sin gluten' o 'tenéis parking'",
          },
        },
        required: ["query"],
      },
    },
    {
      name: "get_reservation",
      description:
        "Busca las próximas reservas activas de un cliente usando su número de teléfono. Útil cuando piden ver, cancelar o modificar su reserva.",
      input_schema: {
        type: "object",
        properties: {
          customer_phone: {
            type: "string",
            description: "El teléfono del cliente, que es: " + from,
          },
        },
        required: ["customer_phone"],
      },
    },
    {
      name: "modify_reservation",
      description:
        "Modifica una reserva existente (cambia fecha, hora o comensales). Verifica disponibilidad y requiere ID de reserva. IMPORTANTE: pregunta antes si el cliente tiene alguna nota/preferencia/alergia y pásala en el campo 'notes' si la proporciona.",
      input_schema: {
        type: "object",
        properties: {
          reservation_id: {
            type: "string",
            description:
              "El ID UUID exacto de la base de datos recuperado con get_reservation. NO lo inventes.",
          },
          new_date: { type: "string", description: "YYYY-MM-DD" },
          new_time: { type: "string", description: "HH:MM" },
          new_guests: { type: "integer" },
          notes: {
            type: "string",
            description:
              "Notas, preferencias o alergias del cliente. Solo incluir si el cliente las proporciona, para no sobreescribir notas anteriores.",
          },
        },
        required: ["reservation_id", "new_date", "new_time", "new_guests"],
      },
    },
    {
      name: "create_reservation",
      description:
        "Crea la reserva definitiva en la base de datos tras recibir la confirmación explícita final del usuario. Necesita todos los parámetros listados. NOTA: Asegúrate de que la reserva cumple con las políticas del restaurante encontradas en 'search_knowledge' (ej: máximo de personas). IMPORTANTE: pregunta antes si el cliente tiene alguna nota/preferencia/alergia y pásala en el campo 'notes'.",
      input_schema: {
        type: "object",
        properties: {
          customer_name: { type: "string", description: "Nombre del cliente" },
          customer_phone: {
            type: "string",
            description: "Su tlf, que es: " + from,
          },
          booking_date: { type: "string", description: "YYYY-MM-DD" },
          booking_time: { type: "string", description: "HH:MM" },
          guests: { type: "integer" },
          notes: {
            type: "string",
            description:
              "Notas, preferencias o alergias del cliente indicadas por él. Ejemplo: 'Sin gluten, terraza si es posible'. Vacío o ausente si el cliente no indicó nada.",
          },
        },
        required: [
          "customer_name",
          "customer_phone",
          "booking_date",
          "booking_time",
          "guests",
        ],
      },
      cache_control: { type: "ephemeral" },
    },
  ];

  let chatMessages = [
    ...conversationHistory.map((h) => ({ role: h.role, content: h.content })),
    { role: "user", content: finalUserMessage },
  ];

  // Configurado para usar exclusivamente Claude Sonnet 4.6 por máxima fiabilidad
  const targetModel = "claude-sonnet-4-6";
  const usedSonnet = true;

  let replyText = "";
  let lastUuidFound = null;
  let toolCalledInThisTurn = false;
  const maxSteps = 5;

  console.log(`[Claude] Starting loop with model: ${targetModel}`);
  for (let i = 0; i < maxSteps; i++) {
    console.log(`[Claude] Step ${i + 1}/${maxSteps}...`);
    let response = await anthropic.messages.create({
      model: targetModel,
      system: [
        {
          type: "text",
          text: systemPrompt,
          cache_control: { type: "ephemeral" },
        },
      ],
      messages: chatMessages,
      tools: anthropicTools,
      max_tokens: 1024,
    });

    // Usando Claude Sonnet 4.6 para todas las etapas por su alta precisión con herramientas.

    chatMessages.push({
      role: "assistant",
      content: response.content,
    });

    console.log(`[Claude] Stop reason: ${response.stop_reason}`);

    if (response.stop_reason === "tool_use") {
      const toolResults = [];

      for (const block of response.content) {
        if (block.type === "tool_use") {
          toolCalledInThisTurn = true;
          const { id, name, input } = block;
          let result;

          if (name === "check_availability") {
            result = await checkAvailability(
              restaurantId,
              input.date,
              input.time,
              input.guests,
              null,
              timezone,
            );
            console.log(
              `[Tool] check_availability result: ${JSON.stringify(result)}`,
            );
            if (result.available) {
              result.instructions =
                "HUECO LIBRE. NO confirmes todavía. El siguiente paso OBLIGATORIO es llamar a 'create_reservation' para guardar los datos. Solo cuando esa herramienta te devuelva éxito podrás dar el link al cliente.";
            }
          } else if (name === "consultar_disponibilidad_semanal") {
            result = await getWeeklyAvailabilityReport(restaurantId, timezone);
            console.log(`[Tool] consultar_disponibilidad_semanal executed.`);
          } else if (name === "search_knowledge") {
            const res = await openai.embeddings.create({
              model: "text-embedding-3-small",
              input: input.query,
            });
            const { data: matches } = await supabaseAdmin.rpc(
              "match_restaurant_knowledge",
              {
                query_embedding: res.data[0].embedding,
                match_threshold: 0.15,
                match_count: 5,
                p_restaurant_id: restaurantId,
              },
            );
            result =
              matches && matches.length > 0
                ? matches
                    .map((m) => `[${m.title || "Info"}]\n${m.content}`)
                    .join("\n\n---\n\n") +
                  "\n\n(AVISO TÉCNICO: Ignora cualquier link de reserva que veas arriba. Solo usa 'create_reservation')."
                : "No hay información registrada sobre ese tema en el restaurante.";
          } else if (name === "get_reservation") {
            const { data, error } = await supabaseAdmin
              .from("reservations")
              .select("id, booking_date, booking_time, guests, status")
              .eq("restaurant_id", restaurantId)
              .eq("customer_phone", from) // FIX: Uses authenticated sender phone, preventing prompt-injection spoofing
              .in("status", ["confirmed", "pending"])
              .gte("booking_date", today)
              .order("booking_date", { ascending: true });

            if (error) {
              result = { success: false, error: "Error al buscar reservas." };
            } else {
              result =
                data && data.length > 0
                  ? { success: true, reservations: data }
                  : {
                      success: true,
                      message:
                        "No se encontraron reservas futuras activas para este número.",
                    };
            }
          } else if (name === "modify_reservation") {
            const isUUID =
              /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(
                input.reservation_id,
              );
            if (!isUUID) {
              result = {
                success: false,
                error:
                  "reservation_id inválido. DEBES usar 'get_reservation' primero para obtener el UUID real de la base de datos de este cliente. NUNCA lo inventes.",
              };
            } else {
              const avail = await checkAvailability(
                restaurantId,
                input.new_date,
                input.new_time,
                input.new_guests,
                input.reservation_id,
                timezone,
              );
              if (!avail.available) {
                result = { success: false, error: avail.reason };
              } else {
                const modifyPayload = {
                  booking_date: input.new_date,
                  booking_time: input.new_time,
                  guests: input.new_guests,
                };
                // Only update notes if the client provided them, to avoid overwriting existing ones
                if (input.notes && input.notes.trim() !== "") {
                  modifyPayload.notes = input.notes.trim();
                }
                const { data: res, error } = await supabaseAdmin
                  .from("reservations")
                  .update(modifyPayload)
                  .eq("id", input.reservation_id)
                  .eq("restaurant_id", restaurantId)
                  .eq("customer_phone", from) // FIX: IDOR protection. Only allow modifying if it belongs to the authenticated caller
                  .select("id")
                  .maybeSingle();

                if (error) {
                  console.error(error);
                  result = {
                    success: false,
                    error: "Error al modificar la reserva en la base de datos.",
                  };
                } else if (!res) {
                  result = {
                    success: false,
                    error:
                      "0 filas actualizadas. El ID provisto no existe en la base de datos. NUNCA INVENTES EL UUID. Usa 'get_reservation' para obtener el verdadero.",
                  };
                } else {
                  const appUrl =
                    config.dashboard_url ||
                    process.env.NEXT_PUBLIC_APP_URL ||
                    "https://app.sirvo.es";
                  const cleanAppUrl = appUrl.replace(/\/$/, "");
                  lastUuidFound = res.id;
                  result = {
                    success: true,
                    link: `${cleanAppUrl}/reserva/${res.id}`,
                    instructions:
                      "¡Modificación exitosa! Comunícaselo al cliente y pásale este link actualizado.",
                  };
                }
              }
            }
          } else if (name === "create_reservation") {
            const avail = await checkAvailability(
              restaurantId,
              input.booking_date,
              input.booking_time,
              input.guests,
              null,
              timezone,
            );
            if (!avail.available) {
              result = { success: false, error: avail.reason };
            } else {
              try {
                await supabaseAdmin.from("clients").upsert(
                  {
                    restaurant_id: restaurantId,
                    phone: fromFormatted, // Normalized: "+34 636123456"
                    name: input.customer_name,
                    last_visit: input.booking_date,
                  },
                  { onConflict: "restaurant_id,phone" },
                );

                // Use client-provided notes, or null if they didn't specify any
                const reservationNotes =
                  input.notes && input.notes.trim() !== ""
                    ? input.notes.trim()
                    : null;

                const generatedShortCode = Math.random()
                  .toString(36)
                  .substring(2, 8)
                  .toUpperCase();

                const { data: res, error } = await supabaseAdmin
                  .from("reservations")
                  .insert({
                    restaurant_id: restaurantId,
                    customer_name: input.customer_name,
                    customer_phone: fromFormatted, // Normalized: "+34 636123456"
                    booking_date: input.booking_date,
                    booking_time: input.booking_time,
                    guests: input.guests,
                    status: "confirmed",
                    notes: reservationNotes,
                    source: "whatsapp",
                    short_code: generatedShortCode,
                  })
                  .select("id")
                  .single();

                console.log("error: ", error);
                console.log("res: ", res);
                if (error) throw error;
                reservationCreated = true;
                console.log(`[Tool] create_reservation SUCCESS: ${res.id}`);

                const appUrl =
                  config.dashboard_url ||
                  process.env.NEXT_PUBLIC_APP_URL ||
                  "https://app.sirvo.es";
                const cleanAppUrl = appUrl.replace(/\/$/, "");
                lastUuidFound = res.id;
                result = {
                  success: true,
                  link: `${cleanAppUrl}/reserva/${res.id}`,
                  instructions:
                    "Comunícale al cliente que su reserva está confirmada, y dale este enlace para que pueda verla, gestionarla o cancelarla.",
                };
              } catch (err) {
                console.error(err);
                result = {
                  success: false,
                  error:
                    "Error de servidor de bases de datos al guardar la reserva.",
                };
              }
            }
          }

          toolResults.push({
            type: "tool_result",
            tool_use_id: id,
            content:
              typeof result === "string" ? result : JSON.stringify(result),
          });
          console.log(`[Tool] ${name} result pushed.`);
        }
      }
      chatMessages.push({ role: "user", content: toolResults });
    } else {
      replyText = response.content.find((b) => b.type === "text")?.text || "";
      break;
    }
  }

  // --- POST-PROCESSING SAFETY CHECK ---
  const isClaimingSuccess =
    /confirmada|apuntado|reservado|listo|hecho/i.test(replyText) &&
    !/un momento|compruebo|miro/i.test(replyText);

  if (replyText.includes("[UUID") || replyText.includes("UUID]")) {
    if (lastUuidFound) {
      console.log(`[Safety] Replacing [UUID] with ${lastUuidFound}`);
      replyText = replyText.replace(
        /\[?UUID(?:_DE_LA_RESERVA)?\]?/gi,
        lastUuidFound,
      );
    } else {
      const historyUuids = JSON.stringify(chatMessages).match(
        /[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/gi,
      );
      if (historyUuids && historyUuids.length > 0) {
        const bestUuid = historyUuids[historyUuids.length - 1];
        console.log(`[Safety] Replacing [UUID] from history with ${bestUuid}`);
        replyText = replyText.replace(
          /\[?UUID(?:_DE_LA_RESERVA)?\]?/gi,
          bestUuid,
        );
      } else if (isClaimingSuccess) {
        console.error(
          "[Safety] CRITICAL: AI claiming success but no UUID found.",
        );
        replyText =
          "¡Listo! Dame un segundo que te confirmo los detalles finales... un momento.";
      }
    }
  }

  // Hallucination check: AI says it's done but no tool was called in this turn
  // and no UUID was found (meaning it's not a follow-up to a previous turn's tool call)
  if (isClaimingSuccess && !toolCalledInThisTurn && !lastUuidFound) {
    const hasUuidInText =
      /[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i.test(
        replyText,
      );
    if (!hasUuidInText) {
      console.warn(
        "[Safety] AI claiming success without tool call or UUID. Intercepting.",
      );
      replyText =
        "Un momento, que estoy terminando de apuntar los datos. Dame un segundo.";
    }
  }

  // Persist conversation history concurrently with sending the message
  const updatedHistory = [
    ...conversationHistory,
    { role: "user", content: finalUserMessage },
    { role: "assistant", content: replyText },
  ].slice(-30);

  const dbPromise = supabaseAdmin.from("whatsapp_sessions").upsert(
    {
      phone: from,
      restaurant_id: restaurantId,
      messages: updatedHistory,
      updated_at: new Date().toISOString(),
    },
    { onConflict: "phone,restaurant_id" },
  );

  // Final Session Lock Check: Verify if a newer message has arrived while Claude was thinking
  const { data: latestMsg } = await supabaseAdmin
    .from("whatsapp_inbox")
    .select("created_at")
    .eq("phone", from)
    .order("created_at", { ascending: false })
    .limit(1)
    .single();

  if (
    latestMsg &&
    new Date(latestMsg.created_at) > new Date(inboxEntry.created_at)
  ) {
    console.log(
      `[Clustering] Aborting delivery for ${from}: Newer message detected at T+${Date.now() - new Date(inboxEntry.created_at).getTime()}ms`,
    );
    return;
  }

  // Simulate typing delay proportional to reply length
  await typingDelay(replyText);

  // Send message, clear reaction, and save DB all concurrently
  console.log(
    `[Delivery] Sending final reply to ${from}: "${replyText.slice(0, 50)}..."`,
  );
  await Promise.all([
    sendReaction(phoneNumberId, from, messageId, "", accessToken),
    sendMessage(phoneNumberId, from, replyText, accessToken),
    Promise.resolve(dbPromise).catch((err) =>
      console.error("History saving error:", err),
    ),
  ]).catch((err) => {
    console.error("[Delivery] FATAL ERROR sending message:", err);
  });
}

/**
 * Calculates adaptive debounce time based on message length.
 * Short fragments wait longer for follow-up, full sentences wait less.
 */
function getDebounceMs(text) {
  const words = text.trim().split(/\s+/).length;
  if (words <= 2) return 300; // Ultra-fast for "Hola", "Ok"
  if (words <= 6) return 100; // Fast for "Quiero una mesa"
  return 50; // Almost immediate for long paragraphs (already clustered by the user typing it)
}
