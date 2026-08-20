<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="w-full">
        <div x-data="() => ({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            internalState: null,
            currentDate: new Date(),
            selectedDate: null,
            calendarOpen: true,
            daysOfWeek: @js($getDaysOfWeek()),
            maxDate: @js($getMaxDate()),
            minDate: @js($getMinDate()),
            disabledDates: @js($getDisabledDates()),
            isDisabled: @js($getIsDisabled()),
            initialMonthYear: @js($getCurrentMonthYear()),
            monthsOfYear: @js($getMonthsOfYear()),
            locale: @js($getCalendarLocaleForFrontend()),

            getMonthYearForDate(date) {
                const year = date.getFullYear();
                const month = date.getMonth();
                return `${this.monthsOfYear[month]} ${year}`;
            },

            initializeFromState() {
                if (this.state) {
                    const date = new Date(this.state);
                    if (!isNaN(date.getTime())) {
                        this.selectedDate = date;
                        this.currentDate = new Date(date.getFullYear(), date.getMonth(), 1);
                        this.internalState = this.state;
                        this.calendarOpen = false;
                    }
                } else {
                    this.selectedDate = null;
                    this.currentDate = new Date();
                    this.internalState = null;
                    this.state = null;
                    this.calendarOpen = true;
                }
            },

            syncFromState() {
                if (this.state !== this.internalState) {
                    this.internalState = this.state;
                    if (this.state) {
                        const date = new Date(this.state);
                        if (!isNaN(date.getTime())) {
                            this.selectedDate = date;
                            this.currentDate = new Date(date.getFullYear(), date.getMonth(), 1);
                            this.calendarOpen = false;
                        }
                    } else {
                        this.selectedDate = null;
                        this.calendarOpen = true;
                    }
                }
            },

            isDateDisabled(date) {
                const dateString = date.toISOString().split('T')[0];
                // Check if date is in disabled dates array
                if (this.disabledDates && this.disabledDates.includes(dateString)) {
                    return true;
                }

                // Check if date is before min date
                if (this.minDate && dateString < this.minDate) {
                    return true;
                }

                // Check if date is after max date
                if (this.maxDate && dateString > this.maxDate) {
                    return true;
                }

                return false;
            },

            canNavigateToPreviousMonth() {
                if (this.isDisabled) return false;
                if (!this.minDate) return true;

                const minDateObj = new Date(this.minDate);
                const prevMonth = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1);

                return prevMonth >= new Date(minDateObj.getFullYear(), minDateObj.getMonth(), 1);
            },

            canNavigateToNextMonth() {
                if (this.isDisabled) return false;
                if (!this.maxDate) return true;

                const maxDateObj = new Date(this.maxDate);
                const nextMonth = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1);

                return nextMonth <= new Date(maxDateObj.getFullYear(), maxDateObj.getMonth(), 1);
            },

            isDateSelectable(dateObj) {
                if (this.isDisabled) return false;
                return dateObj.isCurrentMonth && !this.isDateDisabled(dateObj.date);
            },

            get calendarWeeks() {
                const year = this.currentDate.getFullYear();
                const month = this.currentDate.getMonth();
                const firstDay = new Date(year, month, 1);
                const lastDay = new Date(year, month + 1, 0);
                const daysInMonth = lastDay.getDate();
                const startingDayOfWeek = firstDay.getDay();

                const prevMonth = new Date(year, month - 1, 0);
                const daysInPrevMonth = prevMonth.getDate();

                const days = [];

                // Previous month days
                for (let i = startingDayOfWeek - 1; i >= 0; i--) {
                    const day = daysInPrevMonth - i;
                    const date = new Date(year, month - 1, day);
                    days.push({
                        day,
                        date,
                        isCurrentMonth: false,
                        isSelected: this.isSameDay(date, this.selectedDate),
                        isToday: this.isSameDay(date, new Date()),
                        isDisabled: this.isDateDisabled(date),
                        key: `prev-${day}`
                    });
                }

                // Current month days
                for (let day = 1; day <= daysInMonth; day++) {
                    const date = new Date(year, month, day);
                    const isDisabled = this.isDateDisabled(date);
                    days.push({
                        day,
                        date,
                        isCurrentMonth: true,
                        isSelected: this.isSameDay(date, this.selectedDate),
                        isToday: this.isSameDay(date, new Date()),
                        isDisabled: isDisabled,
                        key: `current-${day}`
                    });
                }

                // Next month days
                const remainingDays = 42 - days.length;
                for (let day = 1; day <= remainingDays; day++) {
                    const date = new Date(year, month + 1, day);
                    days.push({
                        day,
                        date,
                        isCurrentMonth: false,
                        isSelected: this.isSameDay(date, this.selectedDate),
                        isToday: this.isSameDay(date, new Date()),
                        isDisabled: this.isDateDisabled(date),
                        key: `next-${day}`
                    });
                }

                const weeks = [];
                for (let i = 0; i < days.length; i += 7) {
                    weeks.push({
                        key: `week-${Math.floor(i / 7)}`,
                        days: days.slice(i, i + 7)
                    });
                }

                return weeks;
            },

            previousMonth() {
                if (this.canNavigateToPreviousMonth()) {
                    this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1);
                }
            },

            nextMonth() {
                if (this.canNavigateToNextMonth()) {
                    this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1);
                }
            },

            isMobileViewport() {
                try {
                    return typeof window !== 'undefined' && window.matchMedia('(max-width: 768px)').matches;
                } catch (e) {
                    return false;
                }
            },

            selectDate(dateObj) {
                if (this.isDateSelectable(dateObj)) {
                    // If already selected, deselect it
                    if (this.isSameDay(dateObj.date, this.selectedDate)) {
                        this.selectedDate = null;
                        this.internalState = null;
                        this.state = null;
                        this.calendarOpen = true;
                    } else {
                        // Select the new date
                        this.selectedDate = new Date(dateObj.date);
                        this.internalState = this.selectedDate.toISOString().split('T')[0];
                        this.state = this.internalState;
                        this.calendarOpen = false;
                    }
                }
            },

            goToToday() {
                const today = new Date();
                if (!this.isDateDisabled(today) && !this.isDisabled) {
                    this.currentDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    this.selectedDate = today;
                    this.internalState = today.toISOString().split('T')[0];
                    this.state = this.internalState;
                }
            },

            clearSelection() {
                if (!this.isDisabled) {
                    this.selectedDate = null;
                    this.internalState = null;
                    this.state = null;
                }
            },

            isSameDay(date1, date2) {
                return date1?.getDate() === date2?.getDate() &&
                    date1?.getMonth() === date2?.getMonth() &&
                    date1?.getFullYear() === date2?.getFullYear();
            },

            formatStateDate(dateString) {
                const date = new Date(dateString);
                return isNaN(date) ? dateString : date.toLocaleDateString(this.locale, {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            },

            formatDateLabel(date) {
                return date.toLocaleDateString(this.locale, {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            },

            getCurrentMonthYearFormatted() {
                return this.initialMonthYear || this.currentDate.toLocaleDateString(this.locale, {
                    year: 'numeric',
                    month: 'long'
                });
            },

            getPreviousMonthLabel() {
                return this.$t ? this.$t('datepicker.previous_month') : 'Previous month';
            },

            getNextMonthLabel() {
                return this.$t ? this.$t('datepicker.next_month') : 'Next month';
            }

        })"
             x-init="$nextTick(() => {
            initializeFromState();
            $watch('state', () => syncFromState());
        });"
            {{ $getExtraAttributeBag() }}>
            <div class="nova-calendar relative rounded-xl isolate"
                 :class="{ 'opacity-60': isDisabled }">
                <!-- Calendar container -->
                <div class="relative flex justify-center gap-4 p-4">
                    <div class="w-full">
                        <!-- Month heading with navigation -->
                        <div class="nova-calendar__nav" x-show="calendarOpen" x-cloak>
                            <button @click="previousMonth()" type="button" :disabled="!canNavigateToPreviousMonth()"
                                    class="nova-calendar__nav-btn"
                                    :aria-label="getPreviousMonthLabel()">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                    <path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd"></path>
                                </svg>
                            </button>

                            <div class="nova-calendar__month" x-text="getMonthYearForDate(currentDate)"></div>

                            <button @click="nextMonth()" type="button" :disabled="!canNavigateToNextMonth()"
                                    class="nova-calendar__nav-btn"
                                    :aria-label="getNextMonthLabel()">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                    <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="nova-calendar__summary" x-show="!calendarOpen" x-cloak>
                            <div class="nova-calendar__summary-text" x-text="selectedDate ? formatStateDate(internalState) : ''"></div>
                            <button type="button" class="nova-calendar__summary-btn" @click="calendarOpen = true">
                                Cambiar día
                            </button>
                        </div>

                        <div class="w-full" x-show="calendarOpen" x-cloak>
                            <div class="nova-calendar__grid-wrap">
                                <div class="nova-calendar__weekdays">
                                    <template x-for="day in daysOfWeek" :key="day">
                                        <div class="nova-calendar__weekday" x-text="day"></div>
                                    </template>
                                </div>

                                <div class="nova-calendar__weeks">
                                    <template x-for="week in calendarWeeks" :key="week.key">
                                        <div class="nova-calendar__week">
                                            <template x-for="date in week.days" :key="date.key">
                                                <div class="nova-calendar__cell"
                                                     :class="{
                                                        'nova-calendar__cell--other-month': !date.isCurrentMonth,
                                                        'nova-calendar__cell--disabled': date.isDisabled
                                                     }"
                                                     :data-selected="date.isSelected ? '' : null"
                                                     :data-today="date.isToday ? '' : null"
                                                     :data-disabled="date.isDisabled ? '' : null"
                                                     role="gridcell"
                                                     :aria-selected="date.isSelected"
                                                     :aria-disabled="date.isDisabled">
                                                    <button @click="selectDate(date)" type="button"
                                                            :disabled="!isDateSelectable(date)"
                                                            class="nova-calendar__day-btn"
                                                            :class="{
                                                                'nova-calendar__day-btn--selected': date.isSelected && !date.isDisabled,
                                                                'nova-calendar__day-btn--today': date.isToday && !date.isSelected,
                                                                'nova-calendar__day-btn--disabled': !isDateSelectable(date)
                                                            }"
                                                            :aria-label="formatDateLabel(date.date)"
                                                            :tabindex="isDateSelectable(date) ? 0 : -1">
                                                        <span x-text="date.day"></span>
                                                        <span x-show="date.isToday" class="nova-calendar__today-dot"></span>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
