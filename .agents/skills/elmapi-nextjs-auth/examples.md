# Next.js Auth Examples

Use these templates as the baseline for new apps.

## `auth.ts` (NextAuth + Elmapi credentials + refresh rotation)

```typescript
import { createClient, AuthenticationError } from "@elmapicms/js-sdk";
import NextAuth from "next-auth";
import Credentials from "next-auth/providers/credentials";

function elmapiClient() {
  const projectId = process.env.ELMAPI_PROJECT_ID;
  if (!projectId) {
    return null;
  }
  return createClient({
    baseUrl: process.env.ELMAPI_BASE_URL!,
    projectId,
  });
}

async function refreshElmapiToken(token: any) {
  const client = elmapiClient();
  if (!client || !token.refreshToken) {
    return { ...token, authError: "refresh_unavailable" };
  }

  client.projectUserAuth.setSession({
    accessToken: token.accessToken,
    refreshToken: token.refreshToken,
    expiresAt: token.expiresAt,
  });

  try {
    const refreshed = await client.refreshSession();
    return {
      ...token,
      accessToken: refreshed.access_token,
      refreshToken: refreshed.refresh_token ?? token.refreshToken,
      expiresAt: refreshed.expires_at,
      authError: undefined,
    };
  } catch {
    return { ...token, authError: "refresh_failed" };
  }
}

export const { handlers, auth, signIn, signOut } = NextAuth({
  trustHost: true,
  providers: [
    Credentials({
      credentials: {
        email: { label: "Email", type: "email" },
        password: { label: "Password", type: "password" },
      },
      async authorize(credentials) {
        const email = String(credentials?.email ?? "").trim();
        const password = String(credentials?.password ?? "");
        if (!email || !password) {
          return null;
        }

        const client = elmapiClient();
        if (!client) {
          return null;
        }

        try {
          const result = await client.signInWithPassword({ email, password });
          const me = (await client.me()) as Record<string, unknown>;

          return {
            id: String(me.uuid ?? me.id ?? email),
            email: String(me.email ?? email),
            name: (me.display_name as string | undefined) ?? undefined,
            accessToken: result.access_token,
            refreshToken: result.refresh_token ?? "",
            expiresAt: result.expires_at,
          };
        } catch {
          return null;
        }
      },
    }),
  ],
  callbacks: {
    async jwt({ token, user }) {
      if (user) {
        token.sub = user.id;
        token.email = user.email ?? undefined;
        token.name = user.name ?? undefined;
        token.accessToken = user.accessToken;
        token.refreshToken = user.refreshToken;
        token.expiresAt = user.expiresAt;
        return token;
      }

      if (!token.expiresAt) {
        return token;
      }

      const isExpired = Date.now() >= new Date(String(token.expiresAt)).getTime();
      if (!isExpired) {
        return token;
      }

      return refreshElmapiToken(token);
    },
    async session({ session, token }) {
      if (session.user) {
        session.user.id = String(token.sub ?? "");
        session.user.email = token.email as string | undefined;
        session.user.name = token.name as string | undefined;
      }
      session.accessToken = token.accessToken as string | undefined;
      session.authError = token.authError as string | undefined;
      return session;
    },
  },
  session: { strategy: "jwt" },
});
```

## `/api/logout` route (backend revoke first)

```typescript
import { AuthenticationError, createClient } from "@elmapicms/js-sdk";
import { getToken } from "next-auth/jwt";
import { NextRequest, NextResponse } from "next/server";

export async function POST(req: NextRequest) {
  const secret = process.env.AUTH_SECRET;
  const projectId = process.env.ELMAPI_PROJECT_ID;
  if (!secret || !projectId) {
    return NextResponse.json({ error: "Misconfigured" }, { status: 500 });
  }

  const token = await getToken({ req, secret });
  const accessToken = token?.accessToken as string | undefined;
  const refreshToken = token?.refreshToken as string | undefined;

  if (!accessToken) {
    return NextResponse.json({ ok: true, revoked: false }, { status: 200 });
  }

  const client = createClient({
    baseUrl: process.env.ELMAPI_BASE_URL!,
    projectId,
    projectUserAuth: {
      accessToken,
      refreshToken,
      autoRefresh: false,
    },
  });

  try {
    await client.signOut();
    return NextResponse.json({ ok: true, revoked: true }, { status: 200 });
  } catch (error) {
    if (error instanceof AuthenticationError) {
      return NextResponse.json(
        { ok: true, revoked: false, reason: "already_invalid" },
        { status: 200 },
      );
    }
    return NextResponse.json(
      { ok: false, revoked: false, reason: "revoke_failed" },
      { status: 502 },
    );
  }
}
```

## Register flow rule (avoid double sessions)

```typescript
// If signUp already returned access_token, do NOT call signIn again.
// Instead create app session from existing token path, or redirect to app.
if (signUpResult.access_token) {
  // skip second credentials sign-in
}
```
