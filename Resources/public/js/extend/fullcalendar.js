define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    require('fullcalendar');

    /* This extension of fullcalendar lib aims to:
        - take in account calendarUid field of an event during sort of segs;
            it's solved in 'compareSegs' function and rest of code was copied since the lib widely uses functions
            declared in own scope
     */
    var extendedMethods = (function() {
        var extendedMethods;
        // Following code is ignored by codestyle checking since was copied 'as is' from the fullcalendal lib
        // jscs:disable
        function computeSlotSegCoords(seg, seriesBackwardPressure, seriesBackwardCoord) {
            var forwardSegs = seg.forwardSegs;
            var i;

            if (seg.forwardCoord === undefined) { // not already computed

                if (!forwardSegs.length) {

                    // if there are no forward segments, this segment should butt up against the edge
                    seg.forwardCoord = 1;
                }
                else {

                    // sort highest pressure first
                    forwardSegs.sort(compareForwardSlotSegs);

                    // this segment's forwardCoord will be calculated from the backwardCoord of the
                    // highest-pressure forward segment.
                    computeSlotSegCoords(forwardSegs[0], seriesBackwardPressure + 1, seriesBackwardCoord);
                    seg.forwardCoord = forwardSegs[0].backwardCoord;
                }

                // calculate the backwardCoord from the forwardCoord. consider the series
                seg.backwardCoord = seg.forwardCoord -
                    (seg.forwardCoord - seriesBackwardCoord) / // available width for series
                    (seriesBackwardPressure + 1); // # of segments in the series

                // use this segment's coordinates to computed the coordinates of the less-pressurized
                // forward segments
                for (i=0; i<forwardSegs.length; i++) {
                    computeSlotSegCoords(forwardSegs[i], 0, seg.forwardCoord);
                }
            }
        }

        function compareForwardSlotSegs(seg1, seg2) {
            // put higher-pressure first
            return seg2.forwardPressure - seg1.forwardPressure ||
                // put segments that are closer to initial edge first (and favor ones with no coords yet)
                (seg1.backwardCoord || 0) - (seg2.backwardCoord || 0) ||
                // do normal sorting...
                compareSegs(seg1, seg2);
        }

        function placeSlotSegs(segs) {
            var levels;
            var level0;
            var i;

            segs.sort(compareSegs); // order by date
            levels = buildSlotSegLevels(segs);
            computeForwardSlotSegs(levels);

            if ((level0 = levels[0])) {

                for (i = 0; i < level0.length; i++) {
                    computeSlotSegPressures(level0[i]);
                }

                for (i = 0; i < level0.length; i++) {
                    computeSlotSegCoords(level0[i], 0, 0);
                }
            }
        }

        function buildSlotSegLevels(segs) {
            var levels = [];
            var i, seg;
            var j;

            for (i=0; i<segs.length; i++) {
                seg = segs[i];

                // go through all the levels and stop on the first level where there are no collisions
                for (j=0; j<levels.length; j++) {
                    if (!computeSlotSegCollisions(seg, levels[j]).length) {
                        break;
                    }
                }

                seg.level = j;

                (levels[j] || (levels[j] = [])).push(seg);
            }

            return levels;
        }

        function computeSlotSegCollisions(seg, otherSegs, results) {
            results = results || [];

            for (var i=0; i<otherSegs.length; i++) {
                if (isSlotSegCollision(seg, otherSegs[i])) {
                    results.push(otherSegs[i]);
                }
            }

            return results;
        }

        function isSlotSegCollision(seg1, seg2) {
            return seg1.bottom > seg2.top && seg1.top < seg2.bottom;
        }

        function computeSlotSegPressures(seg) {
            var forwardSegs = seg.forwardSegs;
            var forwardPressure = 0;
            var i, forwardSeg;

            if (seg.forwardPressure === undefined) { // not already computed

                for (i=0; i<forwardSegs.length; i++) {
                    forwardSeg = forwardSegs[i];

                    // figure out the child's maximum forward path
                    computeSlotSegPressures(forwardSeg);

                    // either use the existing maximum, or use the child's forward pressure
                    // plus one (for the forwardSeg itself)
                    forwardPressure = Math.max(
                        forwardPressure,
                        1 + forwardSeg.forwardPressure
                    );
                }

                seg.forwardPressure = forwardPressure;
            }
        }

        function computeForwardSlotSegs(levels) {
            var i, level;
            var j, seg;
            var k;

            for (i=0; i<levels.length; i++) {
                level = levels[i];

                for (j=0; j<level.length; j++) {
                    seg = level[j];

                    seg.forwardSegs = [];
                    for (k=i+1; k<levels.length; k++) {
                        computeSlotSegCollisions(seg, levels[k], seg.forwardSegs);
                    }
                }
            }
        }

        function isDaySegCollision(seg, otherSegs) {
            var i, otherSeg;

            for (i = 0; i < otherSegs.length; i++) {
                otherSeg = otherSegs[i];

                if (
                    otherSeg.leftCol <= seg.rightCol &&
                    otherSeg.rightCol >= seg.leftCol
                ) {
                    return true;
                }
            }

            return false;
        }

        function compareDaySegCols(a, b) {
            return a.leftCol - b.leftCol;
        }

        // jscs:enable
        function compareSegs(seg1, seg2) {
            return seg1.eventStartMS - seg2.eventStartMS || // earlier events go first
                seg2.eventDurationMS - seg1.eventDurationMS || // tie? longer events go first
                seg2.event.allDay - seg1.event.allDay || // tie? put all-day events first (booleans cast to 0/1)
                (seg1.event.title || '').localeCompare(seg2.event.title) || // tie? alphabetically by title
                (seg1.event.calendarUid || '').localeCompare(seg2.event.calendarUid); // tie? alphabetically by uid
        }

        extendedMethods = {
            DayGrid: {
                buildSegLevels: function(segs) {
                    // Following code is ignored by checking since was copied 'as is' from the fullcalendal lib
                    // jscs:disable
                    var levels = [];
                    var i, seg;
                    var j;

                    // Give preference to elements with certain criteria, so they have
                    // a chance to be closer to the top.
                    segs.sort(compareSegs);

                    for (i = 0; i < segs.length; i++) {
                        seg = segs[i];

                        // loop through levels, starting with the topmost, until the segment doesn't collide with other segments
                        for (j = 0; j < levels.length; j++) {
                            if (!isDaySegCollision(seg, levels[j])) {
                                break;
                            }
                        }
                        // `j` now holds the desired subrow index
                        seg.level = j;

                        // create new level array if needed and append segment
                        (levels[j] || (levels[j] = [])).push(seg);
                    }

                    // order segments left-to-right. very important if calendar is RTL
                    for (j = 0; j < levels.length; j++) {
                        levels[j].sort(compareDaySegCols);
                    }

                    return levels;
                    // jscs:enable
                }
            },
            TimeGrid: {
                renderSegTable: function(segs) {
                    // Following code is ignored by checking since was copied 'as is' from the fullcalendal lib
                    // jscs:disable
                    var tableEl = $('<table><tr/></table>');
                    var trEl = tableEl.find('tr');
                    var segCols;
                    var i, seg;
                    var col, colSegs;
                    var containerEl;

                    segCols = this.groupSegCols(segs); // group into sub-arrays, and assigns 'col' to each seg

                    this.computeSegVerticals(segs); // compute and assign top/bottom

                    for (col = 0; col < segCols.length; col++) { // iterate each column grouping
                        colSegs = segCols[col];
                        placeSlotSegs(colSegs); // compute horizontal coordinates, z-index's, and reorder the array

                        containerEl = $('<div class="fc-event-container"/>');

                        // assign positioning CSS and insert into container
                        for (i = 0; i < colSegs.length; i++) {
                            seg = colSegs[i];
                            seg.el.css(this.generateSegPositionCss(seg));

                            // if the height is short, add a className for alternate styling
                            if (seg.bottom - seg.top < 30) {
                                seg.el.addClass('fc-short');
                            }

                            containerEl.append(seg.el);
                        }

                        trEl.append($('<td/>').append(containerEl));
                    }

                    this.bookendCells(trEl, 'eventSkeleton');

                    return tableEl;
                    // jscs:enable
                }
            }
        };

        return extendedMethods;
    }());

    $.fullCalendar.views.month = _.wrap($.fullCalendar.views.month, function(OriginalFunc, calendar) {
        var monthView = new OriginalFunc(calendar);
        _.extend(monthView.dayGrid, extendedMethods.DayGrid);
        return monthView;
    });

    $.fullCalendar.views.agendaWeek = _.wrap($.fullCalendar.views.agendaWeek, function(OriginalFunc, calendar) {
        var weekView = new OriginalFunc(calendar);
        _.extend(weekView.dayGrid, extendedMethods.DayGrid);
        _.extend(weekView.timeGrid, extendedMethods.TimeGrid);
        return weekView;
    });

    $.fullCalendar.views.agendaDay = _.wrap($.fullCalendar.views.agendaDay, function(OriginalFunc, calendar) {
        var dayView = new OriginalFunc(calendar);
        _.extend(dayView.dayGrid, extendedMethods.DayGrid);
        _.extend(dayView.timeGrid, extendedMethods.TimeGrid);
        return dayView;
    });
});
