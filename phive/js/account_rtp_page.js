
Date.prototype.getWeekNumber = function() {
    var d = new Date(+this);
    d.setHours(0,0,0,0);
    d.setDate(d.getDate()+4-(d.getDay()||7));
    return Math.ceil((((d-new Date(d.getFullYear(),0,1))/8.64e7)+1)/7);
};

var rtp = {

    initOptions: function(options){
        this.from            = 0;
        this.processing      = false;
        this.processingGraph = false;
        this.type            = 'week';
        for(var key in options){
            this[key] = options[key];
        }
    },

    setType: function(obj) {
        var type = $(obj).data('type');
        this.type = type;
        $('a[class="rtp-btn active"]').removeClass('active').addClass('inactive');
        $(obj).removeClass('inactive').addClass('active');
        this.searchGraph(type);
    },

    setTypeMobile: function(obj) {
        var type = $(obj).data('type');
        this.type = type;
        $('a[class="rtp-btn active"]').removeClass('active').addClass('inactive');
        $(obj).removeClass('inactive').addClass('active');
        this.searchMobileGraph(type);
    },

    searchGraph: function(type) {
        if (type == null) {
            type = this.type;
        }
        if (rtp.processingGraph == false) {

            $('#graph-lines').html('<div style="text-align: center;"><img src="/diamondbet/images/ajax-loader.gif"></div>');
            rtp.processingGraph = true;
            //graph.load(data);

            var params = $('#rtp_graph').serialize();

            ajaxGetBoxJson({func: 'rtpGraph', params: params, game: this.gobj.id, rtp: this.rtp, type: type, user_id: this.userId}, cur_lang, 'AccountBox', function(res){
                rtp.processingGraph = false;

                var type_val = '%';

                var mainData = res.data;

                if (mainData.type == 'day') {
                    graph.formatter = function(val, axis) {
                        var dt = new Date(val);
                        var d = (String(dt.getDate()).length == 1) ? '0'+dt.getDate() : dt.getDate();
                        var m = (String(dt.getMonth()+1).length == 1) ? '0'+(dt.getMonth()+1) : (dt.getMonth()+1);
                        return (d+'.'+m+'.'+dt.getFullYear());
                    };
                }
                else if (mainData.type == 'month') {
                    graph.formatter = function(val, axis) {
                        var dt = new Date(val);
                        var m = (String(dt.getMonth()+1).length == 1) ? '0'+(dt.getMonth()+1) : (dt.getMonth()+1);
                        return (m+'.'+dt.getFullYear());
                    };
                }
                else if (mainData.type == 'week') {
                    graph.formatter = function(val, axis) {
                        var dt = new Date(val);
                        return res.translations.week+' '+dt.getWeekNumber();
                    };
                }
                else if (res.type == 'bets_wins') {
                    graph.formatter = function(val, axis) {
                        var dt = new Date(val);
                        var h = (String(dt.getHours()).length == 1) ? '0'+dt.getHours() : dt.getHours();
                        var m = (String(dt.getMinutes()+1).length == 1) ? '0'+dt.getMinutes() : dt.getMinutes();
                        return (h+':'+m);
                    };

                    type_val = ' ' + res.currency;

                    $('#features-stats').html(mainData.feature_spins.remaining + ' / ' + mainData.feature_spins.granted);
                    $('#hitrate-stats').html(mainData.hit_rate);
                    $('#avgtime-stats').html(mainData.avg_time);
                    $('#avgbets-stats').html(mainData.avg_bets);
                    $('#biggestwin').html(mainData.biggest_win);
                    $('#biggestwin-bet').html(mainData.biggest_win_bet);
                    $('#totalspins').html(mainData.total_spins);
                    $('#average_bet_ammount').html(mainData.average_bet_ammount);

                    graph.load(mainData.graph.data1, mainData.graph.data2, type_val, mainData.legends);

                    $('#rtp_range').html(mainData.rtp+'%');
                }

                if(res.type != 'bets_wins'){
                    $('#features-stats').html(mainData.data.feature_spins.remaining + ' / ' + mainData.data.feature_spins.granted);
                    $('#hitrate-stats').html(mainData.data.hit_rate);
                    $('#avgtime-stats').html(mainData.data.avg_time);
                    $('#avgbets-stats').html(mainData.data.avg_bets);
                    $('#biggestwin').html(mainData.data.biggest_win);
                    $('#biggestwin-bet').html(mainData.data.biggest_win_bet);
                    $('#totalspins').html(mainData.data.total_spins);
                    $('#average_bet_ammount').html(mainData.data.average_bet_ammount);

                    graph.load(mainData.data.graph.data1, mainData.data.graph.data2, type_val);

                    $('#rtp_range').html(mainData.data.rtp+'%');
                }
            });
        }
    },

    searchMobileGraph: function(type) {
        if (type == null) {
            type = this.type;
        }
        if (rtp.processingGraph == false) {

            $('#graph-lines').html('<div style="text-align: center;"><img src="/diamondbet/images/ajax-loader.gif"></div>');
            rtp.processingGraph = true;

            var params = $('#rpt-filters-form').serialize();

            ajaxGetBoxJson({func: 'rtpGraph', params: params, game: this.gobj.id, rtp: this.rtp, type: type, user_id: this.userId}, cur_lang, 'MobileMyRTPBox', function(res){
                rtp.processingGraph = false;

                var type_val = '%';

                var mainData = res.data;

                if (mainData.type == 'day') {
                    mobile_graph.formatter = function(val, axis) {
                        var dt = new Date(val);
                        var d = (String(dt.getDate()).length == 1) ? '0'+dt.getDate() : dt.getDate();
                        var m = (String(dt.getMonth()+1).length == 1) ? '0'+(dt.getMonth()+1) : (dt.getMonth()+1);
                        return (d+'.'+m+'.'+dt.getFullYear().toString().substring(2));
                    };
                }
                else if (mainData.type == 'month') {
                    mobile_graph.formatter = function(val, axis) {
                        var dt = new Date(val);
                        var m = (String(dt.getMonth()+1).length == 1) ? '0'+(dt.getMonth()+1) : (dt.getMonth()+1);
                        return (m+'.'+dt.getFullYear().toString().substring(2));
                    };
                }
                else if (mainData.type == 'week') {
                    mobile_graph.formatter = function(val, axis) {
                        var dt = new Date(val);
                        return res.translations.week+' </br>'+dt.getWeekNumber();
                    };
                }
                else if (res.type == 'bets_wins') {
                    mobile_graph.formatter = function(val, axis) {
                        var dt = new Date(val);
                        var h = (String(dt.getHours()).length == 1) ? '0'+dt.getHours() : dt.getHours();
                        var m = (String(dt.getMinutes()+1).length == 1) ? '0'+dt.getMinutes() : dt.getMinutes();
                        return (h+':'+m);
                    };

                    type_val = ' ' + res.currency;

                    $('#features-stats').html(mainData.feature_spins.remaining + ' / ' + mainData.feature_spins.granted);
                    $('#hitrate-stats').html(mainData.hit_rate);
                    $('#avgtime-stats').html(mainData.avg_time);
                    $('#avgbets-stats').html(mainData.avg_bets);
                    $('#biggestwin').html(mainData.biggest_win);
                    $('#biggestwin-bet').html(mainData.biggest_win_bet);
                    $('#totalspins').html(mainData.total_spins);
                    $('#average_bet_ammount').html(mainData.average_bet_ammount);

                    mobile_graph.load(mainData.graph.data1, mainData.graph.data2, type_val, mainData.legends);

                    $('#rtp_range').html(mainData.rtp+'%');
                }

                if(res.type != 'bets_wins'){
                    $('#features-stats').html(mainData.data.feature_spins.remaining + ' / ' + mainData.data.feature_spins.granted);
                    $('#hitrate-stats').html(mainData.data.hit_rate);
                    $('#avgtime-stats').html(mainData.data.avg_time);
                    $('#avgbets-stats').html(mainData.data.avg_bets);
                    $('#biggestwin').html(mainData.data.biggest_win);
                    $('#biggestwin-bet').html(mainData.data.biggest_win_bet);
                    $('#totalspins').html(mainData.data.total_spins);
                    $('#average_bet_ammount').html(mainData.data.average_bet_ammount);

                    mobile_graph.load(mainData.data.graph.data1, mainData.data.graph.data2, type_val);

                    $('#rtp_range').html(mainData.data.rtp+'%');
                }
            });
        }
    },

    getAnchorParams: function(val, params){
        var anchorUrl = '?game='+val.id;
        _.each(['dt_from', 'time_from', 'dt_to', 'time_to'], function(key){
            anchorUrl += '&'+key+'='+params[key];
        });
        return anchorUrl;
    },

    search: function(id, async, next) {

        if (async || rtp.processing == false) {

            if(next){
                this.from += 30;
            }else{
                this.from = 0;
            }

            rtp.processing = true;

            var params = $('#'+id).serialize();
            var json = objectifyForm('#'+id);
            var table = $('#'+id+'_table > tbody');

            if ((id != 'rtp_all' && id != 'rtp_game' && id != 'bets_wins') || !next || next == -1) {
                $('#'+id+'_table>tbody tr').remove();
            }

            var colspan = (id != 'rtp_all' && id != 'rtp_game') ? 6 : 8;

            table.append($('<tr>').attr('id', id+'_ldr').append($('<td>').attr('colspan', colspan).attr('align', 'center').html('<img src="/diamondbet/images/ajax-loader.gif">')));

            var ellipsisLen = 20;
            var rtp_obj = this;

            ajaxGetBoxJson({func: 'rtpSearch', params: params, type: id, from: this.from, game_id: rtp.game_id, user_id: this.userId}, cur_lang, 'AccountBox', function(res){
                if ((id == 'rtp_all' || id == 'rtp_game' || id == 'bets_wins') && next) {
                    $('#'+id+'_table>tbody tr[id='+id+'_ldr]').remove();
                }
                else {
                    $('#'+id+'_table>tbody tr').remove();
                }

                $.each(res.data, function(idx, val) {

                    if (id == 'bets_wins') {
                        table.append($('<tr>')
                                            .append($('<td>').append($('<div>').addClass('rtp-table-image').append($('<img>').attr('src', empty(rtp_obj.game) ? '' : rtp_obj.thumb_dir + rtp_obj.gobj.game_id + '_c.jpg'))))
                                            .append($('<td>').html(val.created_at))
                                            .append($('<td>').html(val.created_at_time))
                                            .append($('<td>').html(val.type == 'bet' ? res.translations.bet : res.translations.win))
                                            .append($('<td>').html(empty(rtp_obj.game) ? '' : rtp_obj.gobj.game_name))
                                            .append($('<td>').html(val.amount))
                        );
                    }
                    else if (id == 'rtp_all') {
                        table.append($('<tr>')
                                            .append($('<td>').append($('<div>').addClass('rtp-table-image').append($('<img>').attr('src', val.img_url))))
                                            .append($('<td>').html(val.start_time_dt))
                                            .append($('<td>').html(val.start_time))
                                            .append($('<td>').html(ellipsis(val.game_name, ellipsisLen)))
                                            .append($('<td>').html(val.rtp_prc+'%'))
                                            .append($('<td>').html(val.rtp_prc_month+'%'))
                                            .append($('<td>').html(val.rtp_prc_month_prev+'%'))
                                            .append($('<td>').html('<a href="'+rtp_obj.getAnchorParams(val, json)+'" class="rtp-btn">'+res.translations.view+'</a>'))
                        );
                    }
                    else if (id == 'rtp_game') {
                        table.append($('<tr>')
                                            .append($('<td>').append($('<div>').addClass('rtp-table-image').append($('<img>').attr('src', val.img_url))))
                                            .append($('<td>').html(val.start_time_dt))
                                            .append($('<td>').html(val.start_time))
                                            .append($('<td>').html(val.game_name))
                                            .append($('<td>').html(val.bet_amount))
                                            .append($('<td>').html(val.win_amount))
                                            .append($('<td>').html(val.rtp))
                                            .append($('<td>').html((val.session_id ? '<a href="?game='+val.id+'&session='+val.session_id+'" class="rtp-btn">'+res.translations.view+'</a>' : '')))
                        );
                    }
                    else {
                        table.append($('<tr>')
                                            .append($('<td>').append($('<div>').addClass('rtp-table-image').append($('<img>').attr('src', val.img_url))))
                                            .append($('<td>').html(val.start_time_dt))
                                            .append($('<td>').html(val.start_time))
                                            .append($('<td>').html(ellipsis(val.game_name, ellipsisLen)))
                                            .append($('<td>').html(val.rtp_prc+'%'))
                                            .append($('<td>').html('<a href="'+rtp_obj.getAnchorParams(val, json)+'" class="rtp-btn">'+res.translations.view+'</a>'))
                        );
                    }
                });

                rtp.processing = false;
            });
        }
    },

    searchMobile: function(id, async, next) {
        if (async || rtp.processing == false) {

            if(next){
                this.from += 30;
            }else{
                this.from = 0;
            }
            rtp.processing = true;
            var form_id = (id === 'rtp_game' || id === 'bet_wins') ? id : 'rpt-filters-form';
            var params = $('#' + form_id).serialize();
            var json = objectifyForm('#' + form_id);
            var table = $('#'+id+'_table > tbody');
            if (this.from == 0) {
                $('#'+id+'_table>tbody tr').remove();
            }

            var colspan = (id != 'rtp_all' && id != 'rtp_game') ? 6 : 8;

            table.append($('<tr>').attr('id', id+'_ldr').append($('<td>').attr('colspan', colspan).attr('align', 'center').html('<img src="/diamondbet/images/ajax-loader.gif">')));

            var rtp_obj = this;

            ajaxGetBoxJson({func: 'rtpSearch', params: params, type: id, from: this.from, game_id: rtp.game_id, user_id: this.userId}, cur_lang, 'MobileMyRTPBox', function(res){
                $('#'+id+'_table>tbody tr[id='+id+'_ldr]').remove();
                $.each(res.data, function(idx, val) {

                    if (id == 'bets_wins') {
                        table.append($('<tr>')
                                            .append($('<td width="40px">').append($('<div>').addClass('rtp-table-image').append($('<img>').attr('src', empty(rtp_obj.game) ? '' : rtp_obj.thumb_dir + rtp_obj.gobj.game_id + '_c.jpg'))))
                                            .append($('<td>').html(val.created_at + ' ' + val.created_at_time))
                                            .append($('<td>').html(val.type))
                                            .append($('<td>').html(val.amount))
                        );
                    }
                    else if (id == 'rtp_all') {
                        table.append($('<tr>').attr('onclick','goTo("'+rtp_obj.getAnchorParams(val, json)+'")')
                                            .append($('<td width="30px">').append($('<div>').addClass('rtp-table-image').append($('<img>').attr('src', val.img_url))))
                                            .append($('<span class="rtp-session-datetime">').html(val.start_time_dt + ' ' + val.start_time))
                                            .append($('<td class="rtp-col-25">').html('<div class="rtp-col-icon"><span class="icon icon-vs-slot-machine-2"></span><span>' + val.rtp_prc  + '% </span></div>'))
                                            .append($('<td class="rtp-col-25">').html('<div class="rtp-col-icon"><span class="icon icon-vs-map-marker"></span><span>' + val.rtp_prc_month  + '% </span></div>'))
                                            .append($('<td class="rtp-col-25">').html('<div class="rtp-col-icon"><span class="icon icon-vs-arrows-last-play"></span><span>' + val.rtp_prc_month_prev  + '% </span></div>'))
                        );
                    }
                    else if (id == 'rtp_game') {
                        table.append($('<tr>')
                                            .append($('<td class="col-img">').append($('<div>').addClass('rtp-table-image').append($('<img>').attr('src', val.img_url))))
                                            .append($('<span class="rtp-session-datetime">').html(val.start_time_dt + ' ' + val.start_time))
                                            .append($('<td class="rtp-col-25">').html('<div class="rtp-col-icon"><span class="icon icon-vs-set-my-limit"></span><span>' + val.bet_amount  + ' </span></div>'))
                                            .append($('<td class="rtp-col-25"">').html('<div class="rtp-col-icon"><span class="icon icon-66027"></span><span>' + val.win_amount  + ' </span></div>'))
                                            .append($('<td class="rtp-col-25">').html('<div class="rtp-col-icon"><span class="icon icon-rtp"></span><span>' + val.rtp  + '% </span></div>'))
                                            .append($('<td>').html((val.session_id ? '<a href="?game='+val.id+'&session='+val.session_id+'" class="rtp-btn rtp-detail-btn icon icon-vs-chevron-left"></a>' : '')))
                        );
                    }
                    else {
                        table.append($('<tr>')
                                            .append($('<td width="40px">').append($('<div>').addClass('rtp-table-image').append($('<img>').attr('src', val.img_url))))
                                            .append($('<td>').html(val.start_time_dt + ' ' + val.start_time))
                                            .append($('<td>').html(val.rtp_prc+'%'))
                                            .append($('<td>').html('<a href="'+rtp_obj.getAnchorParams(val, json)+'" class="rtp-btn rtp-detail-btn icon icon-vs-chevron-left"></a>'))
                        );
                    }
                });

                rtp.processing = false;
            });
        }
    },
};

var graph = {

    initOptions: function(options){
        this.type_val = '%';
        for(var key in options){
            this[key] = options[key];
        }
    },

    formatter: function(val, axis) {
        return 'Week '+val;
    },

    load: function(data1, data2, type_val, legends) {

        this.type_val = type_val;

        var htmlElement = document.querySelector("#graph-lines");
        var style = getComputedStyle(htmlElement);
        var stylesGraph = {
            fillColor: style.getPropertyValue("fill").trim(),
            color: style.getPropertyValue("color").trim(),
            backgroundColor: style.getPropertyValue("background-color").trim(),
            borderColor: style.getPropertyValue("border-color").trim(),
        }

        var graphData = [{
                data: data1,
                color: stylesGraph.fillColor,
                points: {
                    radius: 4,
                    fillColor: stylesGraph.fillColor
                }
            },
            {
                data: data2,
                color: 'red',
                points: {
                    radius: 4,
                    fillColor: 'red'
                }
            }];

        if(!empty(this.session)){
            graphData[0].label = legends.bets;
            graphData[1].label = legends.wins;
        }

        $('#graph-lines').html('');

        $.plot($('#graph-lines'), graphData, {
            series: {
                points: {
                    show: true,
                },
                lines: {
                    show: true,
                },
                shadowSize: 3,
            },
            grid: {
                color: stylesGraph.color,
                backgroundColor: stylesGraph.backgroundColor,
                borderWidth: {top: 0, right: 0, bottom: 1, left: 1},
                borderColor: {
                    left: stylesGraph.borderColor,
                    bottom: stylesGraph.borderColor,
                },
                //margin: 20,
                //labelMargin: 20,
                hoverable: true
            },
            xaxis: {
                ticks: 10,
                show: true,
                font: {
                    color: stylesGraph.color,
                },
                tickColor: 'transparent',
                reserveSpace: true,
                borderWidth: 10,
                autoscaleMargin: 0.01,
                tickFormatter: graph.formatter,
                //minTickSize: [1, "month"],
                tickDecimals: 0,
                //mode: "time",
                //timeformat: "%d.%m.%y",
                alignTicksWithAxis: 1
            },
            yaxis: {
                ticks: 10,
                show: true,
                font: {
                    color: stylesGraph.color,
                },
                tickColor: 'transparent',
                reserveSpace: true,
                borderWidth: 10,
                autoscaleMargin: 0.01,
                //tickFormatter: graph.formatter,
                //minTickSize: [1, "month"],
                tickDecimals: 0,
                //mode: "time",
                //timeformat: "%d.%m.%y",
                alignTicksWithAxis: 1
                //show: false,
                //tickColor: 'transparent'
            }
        });

        var previousPoint = null;

        $('#graph-lines').on('plothover', function (event, pos, item) {
            if (item) {
                if (previousPoint != item.dataIndex) {
                    previousPoint = item.dataIndex;
                    $('#tooltip').remove();
                    var x = item.datapoint[0],
                        y = item.datapoint[1];
                    for (var idx in item.series.xaxis.ticks) {
                        if (item.series.xaxis.ticks[idx].v == x) {
                            graph.showTooltip(item.pageX, item.pageY, y + '' + graph.type_val + ' <br>'+item.series.xaxis.ticks[idx].label);
                            return true;
                        }
                    }
                    graph.showTooltip(item.pageX, item.pageY, y + '' + graph.type_val);
                    //graph.showTooltip(item.pageX, item.pageY, y + '% <br>'+item.series.xaxis.ticks[item.dataIndex].label);
                    //var dt = new Date(x);
                    //var d = (String(dt.getDate()).length == 1) ? '0'+dt.getDate() : dt.getDate();
                    //var m = (String(dt.getMonth()+1).length == 1) ? '0'+(dt.getMonth()+1) : (dt.getMonth()+1);
                    //showTooltip(item.pageX, item.pageY, y + '% <br>'+d+'.'+m+'.'+dt.getFullYear());
                }
            } else {
                $('#tooltip').remove();
                previousPoint = null;
            }
        });
    },

    showTooltip: function(x, y, contents) {
        $('<div id="tooltip">' + contents + '</div>').css({ top: y - 16, left: x + 20 }).appendTo('body').fadeIn();
    }
};

var mobile_graph = {

    initOptions: function(options){
        this.type_val = '%';
        for(var key in options){
            this[key] = options[key];
        }
    },

    formatter: function(val, axis) {
        return 'Week '+val;
    },

    load: function(data1, data2, type_val, legends) {

        this.type_val = type_val;

        var htmlElement = document.querySelector("#graph-lines");
        var style = getComputedStyle(htmlElement);
        var stylesGraph = {
            fillColor: style.getPropertyValue("fill").trim(),
            color: style.getPropertyValue("color").trim(),
            backgroundColor: style.getPropertyValue("background-color").trim(),
            borderColor: style.getPropertyValue("border-color").trim(),
        }

        var graphData = [{
                data: data1,
                color: stylesGraph.fillColor,
                points: { radius: 4, fillColor: stylesGraph.fillColor }
            },
            {
                data: data2,
                color: 'red',
                points: { radius: 4, fillColor: 'red' }
            }];

        if(!empty(this.session)){
            graphData[0].label = legends.bets;
            graphData[1].label = legends.wins;
        }

        $('#graph-lines').html('');

        $.plot($('#graph-lines'), graphData, {
            series: {
                points: {
                    show: true
                },
                lines: {
                    show: true
                },
                shadowSize: 3
            },
            grid: {
                color: stylesGraph.color,
                backgroundColor: stylesGraph.backgroundColor,
                borderWidth: {top: 0, right: 0, bottom: 1, left: 1},
                borderColor: {
                    left: stylesGraph.borderColor,
                    bottom: stylesGraph.borderColor
                },
                //margin: 20,
                //labelMargin: 20,
                hoverable: true
            },
            xaxis: {
                ticks: 5,
                show: true,
                font: {
                    color: stylesGraph.color
                },
                tickColor: 'transparent',
                reserveSpace: true,
                borderWidth: 10,
                autoscaleMargin: 0.01,
                tickFormatter: mobile_graph.formatter,
                //minTickSize: [1, "month"],
                tickDecimals: 0,
                //mode: "time",
                //timeformat: "%d.%m.%y",
                alignTicksWithAxis: 1
            },
            yaxis: {
                ticks: 5,
                show: true,
                font: {
                    color: stylesGraph.color
                },
                tickColor: 'transparent',
                reserveSpace: true,
                borderWidth: 10,
                autoscaleMargin: 0.01,
                //tickFormatter: graph.formatter,
                //minTickSize: [1, "month"],
                tickDecimals: 0,
                //mode: "time",
                //timeformat: "%d.%m.%y",
                alignTicksWithAxis: 1
                //show: false,
                //tickColor: 'transparent'
            }
        });

        var previousPoint = null;

        $('#graph-lines').on('plothover', function (event, pos, item) {
            if (item) {
                if (previousPoint != item.dataIndex) {
                    previousPoint = item.dataIndex;
                    $('#tooltip').remove();
                    var x = item.datapoint[0],
                        y = item.datapoint[1];
                    for (var idx in item.series.xaxis.ticks) {
                        if (item.series.xaxis.ticks[idx].v == x) {
                            mobile_graph.showTooltip(item.pageX, item.pageY, y + '' + mobile_graph.type_val + ' <br>'+item.series.xaxis.ticks[idx].label);
                            return true;
                        }
                    }
                    mobile_graph.showTooltip(item.pageX, item.pageY, y + '' + mobile_graph.type_val);
                    //graph.showTooltip(item.pageX, item.pageY, y + '% <br>'+item.series.xaxis.ticks[item.dataIndex].label);
                    //var dt = new Date(x);
                    //var d = (String(dt.getDate()).length == 1) ? '0'+dt.getDate() : dt.getDate();
                    //var m = (String(dt.getMonth()+1).length == 1) ? '0'+(dt.getMonth()+1) : (dt.getMonth()+1);
                    //showTooltip(item.pageX, item.pageY, y + '% <br>'+d+'.'+m+'.'+dt.getFullYear());
                }
            } else {
                $('#tooltip').remove();
                previousPoint = null;
            }
        });
    },

    showTooltip: function(x, y, contents) {
        $('<div id="tooltip">' + contents + '</div>').css({ top: y - 16, left: x + 20 }).appendTo('body').fadeIn();
    }
};

var rtpTabs = {

    active: 1,

    set: function(id) {

        if(id == this.active) return false;

        $('#rtptabbtn'+this.active).removeClass('active');
        $('#rtptabbtn'+id).addClass('active');

        $('#rtptab'+this.active).hide();
        $('#rtptab'+id).show();

        this.active = id;
    }
};
