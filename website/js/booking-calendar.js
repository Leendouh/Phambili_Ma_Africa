// Booking Availability Calendar with Conflict Detection

class BookingCalendar {
    constructor(options = {}) {
        this.dateInput = document.getElementById(options.dateInputId || 'booking-date');
        this.timeInput = document.getElementById(options.timeInputId || 'booking-time');
        this.serviceSelect = document.getElementById(options.serviceSelectId || 'service-type');
        this.calendarContainer = document.getElementById(options.calendarContainerId || 'calendar-container');
        this.availableDatesContainer = document.getElementById(options.availableDatesId || 'available-dates');

        this.selectedDate = null;
        this.selectedTime = null;
        this.availableDates = [];
        this.bookedSlots = {};

        this.init();
    }

    init() {
        if (!this.dateInput) return;

        // Create calendar container if it doesn't exist
        if (!this.calendarContainer) {
            this.calendarContainer = document.createElement('div');
            this.calendarContainer.id = 'calendar-container';
            this.calendarContainer.className = 'booking-calendar';
            this.dateInput.parentNode.insertBefore(this.calendarContainer, this.dateInput.nextSibling);
        }

        // Add event listeners
        this.dateInput.addEventListener('change', (e) => this.onDateChange(e));
        this.timeInput.addEventListener('change', (e) => this.onTimeChange(e));
        if (this.serviceSelect) {
            this.serviceSelect.addEventListener('change', () => this.loadAvailability());
        }

        // Load availability on page load
        this.loadAvailability();

        // Add calendar UI
        this.renderCalendar();
    }

    async loadAvailability() {
        let serviceId = null;
        if (this.serviceSelect) {
            serviceId = this.serviceSelect.value;
        } else {
            // Try to get service ID from hidden input (for service popup)
            const serviceIdInput = document.getElementById('service_id');
            if (serviceIdInput) {
                serviceId = serviceIdInput.value;
            }
        }
        if (!serviceId) return;

        try {
            const { getServiceAvailability } = await import('./js/firebase/data.js');
            const data = await getServiceAvailability(serviceId);

            if (data.success) {
                this.availableDates = data.available_dates || [];
                this.bookedSlots = data.booked_slots || {};
                this.updateCalendarUI();
            }
        } catch (error) {
            console.error('Error loading availability:', error);
            // Signed-out visitors can't read requests — show an open calendar.
            const today = new Date();
            for (let i = 0; i < 60; i++) {
                const d = new Date(today.getFullYear(), today.getMonth(), today.getDate() + i);
                this.availableDates.push(this.formatDate(d));
            }
            this.updateCalendarUI();
        }
    }

    renderCalendar() {
        const today = new Date();
        const currentMonth = today.getMonth();
        const currentYear = today.getFullYear();

        const calendarHTML = `
            <div class="calendar-header">
                <button class="calendar-nav" data-direction="prev">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h3 id="calendar-month-year">${this.getMonthName(currentMonth)} ${currentYear}</h3>
                <button class="calendar-nav" data-direction="next">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="calendar-grid">
                <div class="calendar-day-header">Sun</div>
                <div class="calendar-day-header">Mon</div>
                <div class="calendar-day-header">Tue</div>
                <div class="calendar-day-header">Wed</div>
                <div class="calendar-day-header">Thu</div>
                <div class="calendar-day-header">Fri</div>
                <div class="calendar-day-header">Sat</div>
            </div>
            <div class="calendar-days" id="calendar-days"></div>
            <div class="calendar-legend">
                <span class="legend-item available"><span class="legend-color"></span> Available</span>
                <span class="legend-item limited"><span class="legend-color"></span> Limited</span>
                <span class="legend-item unavailable"><span class="legend-color"></span> Unavailable</span>
            </div>
        `;

        this.calendarContainer.innerHTML = calendarHTML;

        // Add navigation event listeners
        this.calendarContainer.querySelectorAll('.calendar-nav').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const direction = e.currentTarget.dataset.direction;
                this.navigateMonth(direction);
            });
        });

        this.renderMonthDays(currentMonth, currentYear);
    }

    renderMonthDays(month, year) {
        const daysContainer = document.getElementById('calendar-days');
        if (!daysContainer) return;

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date();

        let html = '';

        // Empty cells for days before the first day of the month
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="calendar-day empty"></div>';
        }

        // Days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const dateStr = this.formatDate(date);
            const isPast = date < today;
            const isAvailable = this.availableDates.includes(dateStr);
            const availability = this.getDateAvailability(dateStr);

            let classes = 'calendar-day';
            if (isPast) classes += ' past';
            else if (availability === 'unavailable') classes += ' unavailable';
            else if (availability === 'limited') classes += ' limited';
            else if (isAvailable) classes += ' available';

            html += `
                <div class="${classes}" data-date="${dateStr}" ${isPast ? 'disabled' : ''}>
                    ${day}
                </div>
            `;
        }

        daysContainer.innerHTML = html;

        // Add click event listeners to available days
        daysContainer.querySelectorAll('.calendar-day:not(.empty):not(.past)').forEach(day => {
            day.addEventListener('click', () => {
                const date = day.dataset.date;
                this.selectDate(date);
            });
        });
    }

    navigateMonth(direction) {
        const monthYearText = document.getElementById('calendar-month-year').textContent;
        const [monthName, year] = monthYearText.split(' ');
        const month = this.getMonthIndex(monthName);
        let newMonth = direction === 'next' ? month + 1 : month - 1;
        let newYear = parseInt(year);

        if (newMonth > 11) {
            newMonth = 0;
            newYear++;
        } else if (newMonth < 0) {
            newMonth = 11;
            newYear--;
        }

        document.getElementById('calendar-month-year').textContent = `${this.getMonthName(newMonth)} ${newYear}`;
        this.renderMonthDays(newMonth, newYear);
    }

    selectDate(dateStr) {
        this.selectedDate = dateStr;
        this.dateInput.value = dateStr;

        // Update visual selection
        this.calendarContainer.querySelectorAll('.calendar-day').forEach(day => {
            day.classList.remove('selected');
            if (day.dataset.date === dateStr) {
                day.classList.add('selected');
            }
        });

        // Trigger change event
        this.dateInput.dispatchEvent(new Event('change'));

        // Update available times for this date
        this.updateAvailableTimes(dateStr);
    }

    updateAvailableTimes(dateStr) {
        const timeSelect = this.timeInput;
        if (!timeSelect) return;

        const bookedTimes = this.bookedSlots[dateStr] || [];
        const allTimes = [
            '08:00', '09:00', '10:00', '11:00', '12:00',
            '13:00', '14:00', '15:00', '16:00', '17:00'
        ];

        // Disable booked times
        timeSelect.querySelectorAll('option').forEach(option => {
            const time = option.value;
            if (bookedTimes.includes(time)) {
                option.disabled = true;
                option.textContent = `${this.formatTime(time)} (Booked)`;
            } else {
                option.disabled = false;
                option.textContent = this.formatTime(time);
            }
        });
    }

    getDateAvailability(dateStr) {
        if (!this.availableDates.includes(dateStr)) return 'unavailable';

        const bookedTimes = this.bookedSlots[dateStr] || [];
        if (bookedTimes.length >= 8) return 'limited'; // More than 80% booked

        return 'available';
    }

    updateCalendarUI() {
        const monthYearText = document.getElementById('calendar-month-year').textContent;
        const [monthName, year] = monthYearText.split(' ');
        const month = this.getMonthIndex(monthName);
        this.renderMonthDays(month, parseInt(year));
    }

    onDateChange(e) {
        const dateStr = e.target.value;
        if (dateStr) {
            this.selectDate(dateStr);
        }
    }

    onTimeChange(e) {
        this.selectedTime = e.target.value;
    }

    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    formatTime(time) {
        const [hours, minutes] = time.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes} ${ampm}`;
    }

    getMonthName(month) {
        const months = ['January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];
        return months[month];
    }

    getMonthIndex(monthName) {
        const months = ['January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];
        return months.indexOf(monthName);
    }
}

// Initialize calendar when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize calendar for booking page
    const bookingCalendar = new BookingCalendar({
        dateInputId: 'booking-date',
        timeInputId: 'booking-time',
        serviceSelectId: 'service-type',
        calendarContainerId: 'calendar-container'
    });

    // Initialize calendar for service popup
    const servicePopupCalendar = new BookingCalendar({
        dateInputId: 'booking_date',
        timeInputId: 'booking_time',
        serviceSelectId: null,
        calendarContainerId: 'service-calendar-container'
    });
});

// Add CSS styles for the calendar
const calendarStyles = document.createElement('style');
calendarStyles.textContent = `
    .booking-calendar {
        margin-top: 15px;
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .calendar-header h3 {
        margin: 0;
        font-size: 18px;
        color: #333;
    }

    .calendar-nav {
        background: none;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 5px 10px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .calendar-nav:hover {
        background: #f5f5f5;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
        margin-bottom: 10px;
    }

    .calendar-day-header {
        text-align: center;
        font-weight: bold;
        color: #666;
        font-size: 12px;
        padding: 5px;
    }

    .calendar-days {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
    }

    .calendar-day {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 14px;
    }

    .calendar-day.empty {
        cursor: default;
    }

    .calendar-day.past {
        color: #ccc;
        cursor: not-allowed;
    }

    .calendar-day.available {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .calendar-day.available:hover {
        background: #c8e6c9;
    }

    .calendar-day.limited {
        background: #fff3e0;
        color: #ef6c00;
    }

    .calendar-day.limited:hover {
        background: #ffe0b2;
    }

    .calendar-day.unavailable {
        background: #ffebee;
        color: #c62828;
        cursor: not-allowed;
    }

    .calendar-day.selected {
        background: #1976d2 !important;
        color: white !important;
        font-weight: bold;
    }

    .calendar-legend {
        display: flex;
        gap: 20px;
        margin-top: 15px;
        font-size: 12px;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .legend-color {
        width: 12px;
        height: 12px;
        border-radius: 2px;
    }

    .legend-item.available .legend-color {
        background: #e8f5e9;
    }

    .legend-item.limited .legend-color {
        background: #fff3e0;
    }

    .legend-item.unavailable .legend-color {
        background: #ffebee;
    }

    @media (max-width: 768px) {
        .booking-calendar {
            padding: 15px;
        }

        .calendar-day {
            font-size: 12px;
        }

        .calendar-legend {
            flex-wrap: wrap;
            gap: 10px;
        }
    }
`;
document.head.appendChild(calendarStyles);
