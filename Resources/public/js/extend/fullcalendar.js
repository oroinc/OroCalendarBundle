define(function(require) {
    const _ = require('underscore');
    const FC = require('fullcalendar/dist/fullcalendar');

    const htmlEscape = FC.htmlEscape;
    const cssToStr = FC.cssToStr;

    FC.AgendaView.mixin({
        // Builds the HTML skeleton for the view.
        // The day-grid and time-grid components will render inside containers defined by this HTML.
        renderSkeletonHtml: function() {
            return `<table>
                <thead class="fc-head">
                    <tr>
                        <td class="fc-head-container ${this.widgetHeaderClass}"></td>
                    </tr>
                </thead>
                <tbody class="fc-body">
                    <tr>
                        <td class="${this.widgetContentClass}">
                        ${this.dayGrid ? `
                            <div class="fc-day-grid"></div>
                            <hr class="fc-divider ${this.widgetHeaderClass}">
                        ` : ''}
                        </td>
                    </tr>
                </tbody>
            </table>`;
        }
    });

    FC.DayGrid.mixin({
        // Builds the HTML to be used for the default element for an individual segment
        fgSegHtml: function(seg, disableResizing) {
            const view = this.view;
            const event = seg.event;
            const isDraggable = view.isEventDraggable(event);
            const isResizableFromStart = !disableResizing && event.allDay &&
                seg.isStart && view.isEventResizableFromStart(event);
            const isResizableFromEnd = !disableResizing && event.allDay &&
                seg.isEnd && view.isEventResizableFromEnd(event);
            const classes = this.getSegClasses(seg, isDraggable, isResizableFromStart || isResizableFromEnd);
            const skinCss = cssToStr(this.getSegSkinCss(seg));
            let timeHtml = '';
            let timeText;

            classes.unshift('fc-day-grid-event', 'fc-h-event');

            // Only display a timed events time if it is the starting segment
            if (seg.isStart) {
                timeText = this.getEventTimeText(event);
                if (timeText) {
                    timeHtml = '<span class="fc-time">' + htmlEscape(timeText) + '</span>';
                }
            }

            const titleHtml = `<span class="fc-title">${htmlEscape(event.title || '') || '&nbsp;'}</span>`;

            return `<a class="${classes.join(' ')}"
                       ${event.url ? `href="${htmlEscape(event.url)}"` : ''}
                       ${skinCss ? `style="${skinCss}"` : ''}
            >
                <div class="fc-content">${this.isRTL ? `${titleHtml} ${timeHtml}` : `${timeHtml} ${titleHtml}`}</div>
                ${isResizableFromStart ? `<div class="fc-resizer fc-start-resizer"></div>` : ''}
                ${isResizableFromEnd ? `<div class="fc-resizer fc-end-resizer"></div>` : ''}
            </a>`;
        }
    });

    FC.TimeGrid.mixin({
        // Renders the HTML for a single event segment's default rendering
        fgSegHtml: function(seg, disableResizing) {
            const view = this.view;
            const event = seg.event;
            const isDraggable = view.isEventDraggable(event);
            const isResizableFromStart = !disableResizing && seg.isStart && view.isEventResizableFromStart(event);
            const isResizableFromEnd = !disableResizing && seg.isEnd && view.isEventResizableFromEnd(event);
            const classes = this.getSegClasses(seg, isDraggable, isResizableFromStart || isResizableFromEnd);
            const skinCss = cssToStr(this.getSegSkinCss(seg));
            let timeText;
            let fullTimeText; // more verbose time text. for the print stylesheet
            let startTimeText; // just the start time text

            classes.unshift('fc-time-grid-event', 'fc-v-event');

            if (view.isMultiDayEvent(event)) { // if the event appears to span more than one day...
                // Don't display time text on segments that run entirely through a day.
                // That would appear as midnight-midnight and would look dumb.
                // Otherwise, display the time text for the *segment's* times (like 6pm-midnight or midnight-10am)
                if (seg.isStart || seg.isEnd) {
                    timeText = this.getEventTimeText(seg);
                    fullTimeText = this.getEventTimeText(seg, 'LT');
                    startTimeText = this.getEventTimeText(seg, null, false); // displayEnd=false
                }
            } else {
                // Display the normal time text for the *event's* times
                timeText = this.getEventTimeText(event);
                fullTimeText = this.getEventTimeText(event, 'LT');
                startTimeText = this.getEventTimeText(event, null, false); // displayEnd=false
            }

            return `<a class="${classes.join(' ')}"
                       ${event.url ? `href="${htmlEscape(event.url)}"` : ''}
                       ${skinCss ? `style="${skinCss}"` : ''}
            >
                <div class="fc-content">
                 ${timeText ? `
                    <div class="fc-time"
                         data-start="${htmlEscape(startTimeText)}"
                         data-full="${htmlEscape(fullTimeText)}"
                         >
                            <span>${htmlEscape(timeText)}</span>
                         </div>
                 ` : ''}
                 ${event.title ? `
                        <div class="fc-title">${htmlEscape(event.title)}</div>
                 ` : ''}
                </div>
                <div class="fc-bg"></div>
                ${isResizableFromEnd ? `
                    <div class="fc-resizer fc-end-resizer"></div>
                ` : ''}
            </a>`;
        }
    });

    FC.Calendar.mixin({
        renderHeader: function() {
            const header = this.header;

            header.setToolbarOptions(this.computeHeaderOptions());
            header.render();

            if (header.el) {
                this.el.prepend(header.el);


                if (_.isRTL()) {
                    const $nextBtn = header.el.find('.fc-left .fc-button-group .fc-next-button');

                    // Swap next and prev buttons
                    $nextBtn.after($nextBtn.prev());
                }
            }
        }
    });

    return FC;
});
