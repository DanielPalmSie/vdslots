<div id="sla-data-range" class="mrp-container ">
    <div class="mrp-monthdisplay form-control">
        <span class="mrp-lowerMonth"></span>
        <span class="mrp-to"> - </span>
        <span class="mrp-upperMonth"></span>
    </div>
    <input type="hidden" name="month-range-start" value="{{ $month_range->getStart()->format('Y-m') }}"
           id="month-range-start"/>
    <input type="hidden" name="month-range-end" value="{{ $month_range->getEnd()->format('Y-m') }}"
           id="month-range-end"/>
</div>
@section('footer-javascript')
    @parent
    <script type="text/javascript">
        var MONTHS = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];

        $(function () {
            startMonth = $('#month-range-start').val().substring(5);
            startYear = $('#month-range-start').val().substring(0, 4);
            endMonth = $('#month-range-end').val().substring(5);
            endYear = $('#month-range-end').val().substring(0, 4);
            setInputVal(startYear, startMonth, endYear, endMonth);

            fiscalMonth = 7;

            if (startMonth < 10)
                startDate = parseInt("" + startYear + startMonth + "");
            else
                startDate = parseInt("" + startYear + startMonth + "");
            if (endMonth < 10)
                endDate = parseInt("" + endYear + endMonth + "");
            else
                endDate = parseInt("" + endYear + endMonth + "");

            var date_for_buttons = new Date();
            content = '<div class="row mpr-calendarholder">';
            content += '<div class="col-1">';
            content += '<button class="btn btn-secondary mpr-select-month" data-year="' + date_for_buttons.getFullYear() + '" data-month="' + (parseInt(date_for_buttons.getMonth()) + 1) + '">This Month</button>';
            content += '<button class="btn btn-secondary mpr-prev-month">Previous Month</button>';
            content += '<button class="btn btn-secondary mpr-add-months" data-months="-6">Last 6 Months</button>';
            content += '<button class="btn btn-secondary mpr-add-months" data-months="-12">Last 12 Months</button>';
            content += '<button class="btn btn-secondary mpr-select-year" data-year="' + (parseInt(date_for_buttons.getFullYear()) - 1) + '">Previous Year</button>';
            content += '<button class="btn btn-secondary mpr-select-year" data-year="' + date_for_buttons.getFullYear() + '">This Year</button>';
            content += '<button class="btn btn-success mpr-close">Close</button>';
            content += '</div>';
            calendarCount = endYear - startYear;
            if (calendarCount == 0)
                calendarCount++;
            var d = new Date();
            for (y = 0; y < 2; y++) {
                content += '<div class="col-6" ><div class="mpr-calendar row" id="mpr-calendar-' + (y + 1) + '">'
                    + '<h5 class="col-12"><i class="mpr-yeardown fa fa-chevron-left"></i><span>' + (startYear + y).toString() + '</span><i class="mpr-yearup fa fa-chevron-right"></i></h5><div class="mpr-monthsContainer"><div class="mpr-MonthsWrapper">';
                for (m = 0; m < 12; m++) {
                    var monthval;
                    if ((m + 1) < 10)
                        monthval = "0" + (m + 1);
                    else
                        monthval = "" + (m + 1);
                    content += '<span data-month="' + monthval + '" class="col-3 mpr-month">' + MONTHS[m] + '</span>';
                }
                content += '</div></div></div></div>';
            }
            content += '</div>';

            $(document).on('click', '.mpr-month', function (e) {
                e.stopPropagation();
                $month = $(this);
                var monthnum = $month.data('month');
                var year = $month.parents('.mpr-calendar').children('h5').children('span').html();
                if ($month.parents('#mpr-calendar-1').length > 0) {
                    //Start Date
                    startDate = parseInt("" + year + monthnum);
                    if (startDate > endDate) {

                        if (year != parseInt(endDate / 100))
                            $('.mpr-calendar:last h5 span').html(year);
                        endDate = startDate;
                    }
                } else {
                    //End Date
                    endDate = parseInt("" + year + monthnum);
                    if (startDate > endDate) {
                        if (year != parseInt(startDate / 100))
                            $('.mpr-calendar:first h5 span').html(year);
                        startDate = endDate;
                    }
                }

                paintMonths();
            });

            $(document).on('click', '.mpr-close', function (e) {
                e.stopPropagation();
                $(".popover").remove();
            });

            $(document).on('click', '.mpr-yearup', function (e) {
                e.stopPropagation();
                var year = parseInt($(this).prev().html());
                year++;
                $(this).prev().html("" + year);
                $(this).parents('.mpr-calendar').find('.mpr-MonthsWrapper').fadeOut(175, function () {
                    paintMonths();
                    $(this).parents('.mpr-calendar').find('.mpr-MonthsWrapper').fadeIn(175);
                });
            });

            $(document).on('click', '.mpr-yeardown', function (e) {
                e.stopPropagation();
                var year = parseInt($(this).next().html());
                year--;
                $(this).next().html("" + year);
                //paintMonths();
                $(this).parents('.mpr-calendar').find('.mpr-MonthsWrapper').fadeOut(175, function () {
                    paintMonths();
                    $(this).parents('.mpr-calendar').find('.mpr-MonthsWrapper').fadeIn(175);
                });
            });

            $(document).on('click', '.mpr-add-months', function (e) {
                e.stopPropagation();
                var d = new Date();
                var currentYear = d.getFullYear();
                var currentMonth = d.getMonth() + 1;
                var months_to_add = parseInt($(this).data('months'));

                var start_month = (currentMonth + months_to_add % 12 + 12) % 12;
                if (start_month === 0) start_month = 12;

                var start_year = currentYear + Math.floor((currentMonth + months_to_add - 1) / 12);

                startDate = parseInt("" + start_year + MONTHS[start_month - 1]);
                endDate = parseInt("" + currentYear + MONTHS[currentMonth - 1]);

                $('.mpr-calendar').each(function (i) {
                    var $cal = $(this);
                    if (i === 0) {
                        $('h5 span', $cal).html(start_year);
                    } else {
                        $('h5 span', $cal).html(currentYear);
                    }
                });

                $('.mpr-calendar').find('.mpr-MonthsWrapper').fadeOut(175, function () {
                    paintMonths();
                    $('.mpr-calendar').find('.mpr-MonthsWrapper').fadeIn(175);
                });
            });

            $(document).on('click', '.mpr-prev-6-months', function (e) {
                e.stopPropagation();
                var d = new Date();
                var month = d.getMonth() + 1;
                var start_month = d.getMonth() - 6;
                var year = d.getFullYear();
                if (month < 6) {
                    start_month = 12 - (6 - month);
                    year--;
                }

                startDate = parseInt("" + year + MONTHS[start_month - 1]);
                endDate = parseInt("" + d.getFullYear() + MONTHS[month - 1]);

                console.log(year);
                $('.mpr-calendar').each(function (i) {
                    var $cal = $(this);
                    if (i == 0) {
                        $('h5 span', $cal).html(year);
                    } else {
                        $('h5 span', $cal).html(d.getFullYear());
                    }
                });
                $('.mpr-calendar').find('.mpr-MonthsWrapper').fadeOut(175, function () {
                    paintMonths();
                    $('.mpr-calendar').find('.mpr-MonthsWrapper').fadeIn(175);
                });
            });

            $(document).on('click', '.mpr-select-year', function (e) {
                e.stopPropagation();
                var d = new Date();
                var year = $(this).data('year');
                startDate = parseInt(year + "01");
                endDate = parseInt(year + "12");
                $('.mpr-calendar').each(function () {
                    var $cal = $(this);
                    $('h5 span', $cal).html(year);
                });
                $('.mpr-calendar').find('.mpr-MonthsWrapper').fadeOut(175, function () {
                    paintMonths();
                    $('.mpr-calendar').find('.mpr-MonthsWrapper').fadeIn(175);
                });
            });

            $(document).on('click', '.mpr-select-month', function (e) {
                e.stopPropagation();
                var d = new Date();
                year = $(this).data('year');
                month = $(this).data('month');
                console.log(month);
                startDate = endDate = parseInt("" + year + MONTHS[month - 1]);
                $('.mpr-calendar').each(function (i) {
                    var $cal = $(this);
                    $('h5 span', $cal).html(year);
                });
                $('.mpr-calendar').find('.mpr-MonthsWrapper').fadeOut(175, function () {
                    paintMonths();
                    $('.mpr-calendar').find('.mpr-MonthsWrapper').fadeIn(175);
                });
            });

            $(document).on('click', '.mpr-prev-month', function (e) {
                e.stopPropagation();
                var d = new Date();
                year = d.getFullYear();
                month = parseInt(d.getMonth()) + 1;
                if (month === 1) {
                    year--;
                    month = 12;
                } else {
                    month--;
                }
                startDate = endDate = parseInt("" + year + MONTHS[month - 1]);
                $('.mpr-calendar').each(function (i) {
                    var $cal = $(this);
                    $('h5 span', $cal).html(year);
                });
                $('.mpr-calendar').find('.mpr-MonthsWrapper').fadeOut(175, function () {
                    paintMonths();
                    $('.mpr-calendar').find('.mpr-MonthsWrapper').fadeIn(175);
                });
            });

            var mprVisible = false;
            var mprpopover = $('.mrp-container').popover({
                container: "body",
                placement: "bottom",
                html: true,
                sanitize: false,
                content: content
            }).on('show.bs.popover', function () {
                $('.popover').remove();
                var waiter = setInterval(function () {
                    if ($('.popover').length > 0) {
                        clearInterval(waiter);
                        setViewToCurrentYears();
                        paintMonths();
                    }
                }, 50);
            }).on('shown.bs.popover', function () {
                mprVisible = true;
            }).on('hidden.bs.popover', function () {
                mprVisible = false;
            });

            $(document).on('click', '.mpr-calendarholder', function (e) {
                e.preventDefault();
                e.stopPropagation();
            });
            $(document).on("click", ".mrp-container", function (e) {
                if (mprVisible) {
                    e.preventDefault();
                    e.stopPropagation();
                    mprVisible = false;
                }
            });
            $(document).on("click", function (e) {
                if (mprVisible) {
                    $('.mpr-calendarholder').parents('.popover').fadeOut(200, function () {
                        $('.mpr-calendarholder').parents('.popover').remove();
                        $('.mrp-container').trigger('click');
                    });
                    mprVisible = false;
                }
            });
        });

        function setViewToCurrentYears() {
            var startyear = parseInt(startDate / 100);
            var endyear = parseInt(endDate / 100);
            $('.mpr-calendar h5 span').eq(0).html(startyear);
            $('.mpr-calendar h5 span').eq(1).html(endyear);
        }

        function paintMonths() {
            $('.mpr-calendar').each(function () {
                var $cal = $(this);
                var year = $('h5 span', $cal).html();
                $('.mpr-month', $cal).each(function (i) {
                    if ((i + 1) > 9)
                        cDate = parseInt("" + year + (i + 1));
                    else
                        cDate = parseInt("" + year + '0' + (i + 1));
                    if (cDate >= startDate && cDate <= endDate) {
                        $(this).addClass('mpr-selected');
                    } else {
                        $(this).removeClass('mpr-selected');
                    }
                });
            });
            $('.mpr-calendar .mpr-month').removeClass("mpr-edge");
            //Write Text
            var startyear = parseInt(startDate / 100);
            var startmonth = parseInt(safeRound((startDate / 100 - startyear)) * 100);
            var endyear = parseInt(endDate / 100);
            var endmonth = parseInt(safeRound((endDate / 100 - endyear)) * 100);
            setInputVal(startyear, MONTHS[startmonth - 1], endyear, MONTHS[endmonth - 1]);
            $('#month-range-start').val(startyear + '-' + startmonth);
            $('#month-range-end').val(endyear + '-' + endmonth);
            if (startyear == parseInt($('.mpr-calendar:first h5 span').html()))
                $('.mpr-calendar:first .mpr-selected:first').addClass("mpr-edge");
            if (endyear == parseInt($('.mpr-calendar:last h5 span').html()))
                $('.mpr-calendar:last .mpr-selected:last').addClass("mpr-edge");
        }

        function safeRound(val) {
            return Math.round(((val) + 0.00001) * 100) / 100;
        }

        function setInputVal(startYear, startMonth, endYear, endMonth) {
            $('.mrp-monthdisplay .mrp-lowerMonth').html(startYear + "-" + startMonth);
            $('.mrp-monthdisplay .mrp-upperMonth').html(endYear + "-" + endMonth);
        }
    </script>
@endsection
@section('header-css')
    @parent
    <style>

        .mrp-monthdisplay {
            cursor: pointer;
        }

        .mrp-to {
            color: #aaa;
            margin-right: 0px;
            margin-left: 0px;
            font-size: 11px;
            text-transform: uppercase;
            padding: 5px 3px 5px 3px;
        }

        .mpr-calendar {
            display: inline-block;
            padding: 3px 5px;
            border-right: solid #999 1px;
        }

        .mpr-calendarholder .col-6:last-of-type .mpr-calendar  {
            border-right: none;
        }

        .mpr-month {
            padding: 20px;
            text-transform: uppercase;
            font-size: 12px;
        }

        .mpr-calendar h5 {
            width: 100%;
            text-align: center;
            font-weight: bold;
            font-size: 18px
        }

        .mpr-selected {
            background-color: #ebf4f8;
            color: #000;
        }

        .mpr-month:hover {
            background: #eee;
            border-radius: 5px;
            cursor: pointer;
        }

        .mpr-selected.mpr-month:hover {
            border-radius: 0px;
            box-shadow: none;
        }

        .mpr-calendarholder .col-6 {
            max-width: 250px;
            min-width: 250px;
        }

        .mpr-calendarholder .col-1 {
            max-width: 150px;
            min-width: 150px;
        }

        .mpr-calendarholder .btn-secondary {
            font-size: 13px;
            background: #f5f5f5;
            border: 1px solid #f5f5f5;
            border-radius: 4px;
            color: #08c;
            padding: 3px 12px;
            margin-bottom: 8px;
            cursor: pointer;
            width: 100%;
        }

        .mpr-calendarholder .btn-secondary:hover {
            background: #08c;
            border: 1px solid #08c;
            color: #fff;
        }

        .mpr-calendarholder .mpr-close {
            width: 100%;
        }

        .mpr-calendar h5 {
            line-height: 38px;
        }

        .mpr-MonthsWrapper {
            display: flex;
            flex-wrap: wrap;
        }

        .mpr-quickset {
            color: #666;
            text-transform: uppercase;
            text-align: center;
        }

        .mpr-yeardown, .mpr-yearup {
            margin-left: 5px;
            cursor: pointer;
            color: #666;
            padding: 10px;
        }

        .mpr-yeardown {
            float: left;
        }

        .mpr-yearup {
            float: right;
        }

        .mpr-yeardown:hover,
        .mpr-yearup:hover {
            background: #eee;
            border-radius: 5px;
            padding: 10px;
        }

        .mpr-yeardown:hover, .mpr-yearup:hover {
            color: #40667A;
        }

        .popover {
            max-width: 1920px !important;
        }

        .mpr-edge,
        .mpr-edge:hover {
            background-color: #357ebd;
            color: #fff;
        }

    </style>
@endsection
