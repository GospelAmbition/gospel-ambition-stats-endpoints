jQuery(function ($) {

  /**
   * EVENT LISTENERS
   */

  $(document).ready(function () {
    new window.Foundation.Reveal($('#ga_metrics_modal'));
  });

  let am5_root = null;
  let am5_chart = null;
  am5.ready(function () {

    // Create root element
    // https://www.amcharts.com/docs/v5/getting-started/#Root_element
    am5_root = am5.Root.new('ga_metrics_modal_content');
    am5_root.tapToActivate = true;

    // Set themes
    // https://www.amcharts.com/docs/v5/concepts/themes/
    am5_root.setThemes([
      am5themes_Animated.new(am5_root)
    ]);
  });

  $(document).on('closed.zf.reveal', '[data-reveal]', function (evt) {

    // Dispose of any existing chart instances.
    if ( am5_chart ) {
      am5_chart.dispose();
    }
  });

  $(document).on('click', '.display-metric-chart', function (e) {
    let project_id = $(e.currentTarget).data('project_id');
    let metric = $(e.currentTarget).data('metric');
    let metric_title = $(e.currentTarget).data('metric_title');
    let metric_type = $(e.currentTarget).data('metric_type');
    let modal = $('#ga_metrics_modal');
    let modal_title = $(modal).find('#ga_metrics_modal_title');
    let modal_content = $(modal).find('#ga_metrics_modal_content');
    let modal_spinner = $(modal).find('.loading-spinner');

    // Display metric modal.
    $(modal_content).fadeOut('fast', function () {
      $(modal_spinner).show();
      $(modal_title).text(encodeURI(metric_title).replace(/%20/g, ' '));
      $(modal).foundation('open');

      // Fetch corresponding metrics.
      let date_start = new Date();
      date_start.setUTCFullYear(date_start.getUTCFullYear() - 1);

      let payload = {
        'site_id': 'gospel_ambition',
        'project_id': project_id,
        'metric': metric,
        'ts_start': parseInt("" + date_start.getTime() / 1000),
        'ts_end': parseInt("" + new Date().getTime() / 1000)
      };

      $.ajax({
        type: 'POST',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        url: window.gospel_ambition_script_obj.root + 'go/v1/metrics',
        data: JSON.stringify(payload),
        beforeSend: (xhr) => {
          xhr.setRequestHeader('X-WP-Nonce', window.gospel_ambition_script_obj.nonce);
        }
      }).promise()
      .then(response => {
        if ( response['metrics'] ) {

          let data = prepare_display_metrics( response['metrics'], payload['ts_start'], payload['ts_end'], metric_type );

          // Create chart
          // https://www.amcharts.com/docs/v5/charts/xy-chart/
          am5_chart = am5_root.container.children.push(am5xy.XYChart.new(am5_root, {
            panX: false,
            panY: false,
            wheelX: 'none',
            wheelY: 'none'
          }));

          // Add cursor
          // https://www.amcharts.com/docs/v5/charts/xy-chart/cursor/
          let cursor = am5_chart.set("cursor", am5xy.XYCursor.new(am5_root, {
          }));
          cursor.lineY.set("visible", false);

          // Create axes
          // https://www.amcharts.com/docs/v5/charts/xy-chart/axes/
          let xAxis = am5_chart.xAxes.push(am5xy.CategoryAxis.new(am5_root, {
              categoryField: "month",
              renderer: am5xy.AxisRendererX.new(am5_root, {
                minGridDistance: 60
              })
            })
          );
          xAxis.data.setAll(data);

          let yAxis = am5_chart.yAxes.push(am5xy.ValueAxis.new(am5_root, {
            renderer: am5xy.AxisRendererY.new(am5_root, {})
          }));

          // Add series
          // https://www.amcharts.com/docs/v5/charts/xy-chart/series/
          let series = am5_chart.series.push(am5xy.ColumnSeries.new(am5_root, {
            name: "Series",
            xAxis: xAxis,
            yAxis: yAxis,
            categoryXField: "month",
            valueYField: "value",
            tooltip: am5.Tooltip.new(am5_root, {
              labelText: '[bold]{year}-{month}-{day}[/]: {valueY}' + ((metric_type === 'minutes') ? ' [bold](yrs)[/]' : '')
            })
          }));

          series.columns.template.setAll({
            strokeOpacity: 1,
            fillOpacity: 0.5,
            strokeWidth: 2,
            cornerRadiusTL: 5,
            cornerRadiusTR: 5
          });
          series.data.setAll(data);

          // Make stuff animate on load
          // https://www.amcharts.com/docs/v5/concepts/animations/
          series.appear(1000);
          am5_chart.appear(1000, 100);

          // Fade in updated chart.
          $(modal_spinner).fadeOut('fast', function () {
            $(modal_content).height(500);
            $(modal_content).fadeIn('fast');
          });
        }
      });
    });
  });

  /**
   * HELPER FUNCTIONS
   */

  function prepare_display_metrics( metrics, ts_start, ts_end, metric_type) {
    let display_metrics = [];
    let date_start = new Date(0);
    date_start.setUTCSeconds(ts_start);

    // Parse metric range.
    for (let a = 0; a < 12; a++) {
      let next_date_month = new Date( date_start.setMonth(date_start.getMonth() + 1) );

      //let next_month = start.add(1, 'months');
      let year = next_date_month.getUTCFullYear();
      let month = next_date_month.getMonth() + 1;
      let day = 1; // Specify date in month to be processed.

      // Attempt to locate corresponding metric.
      let matched_metric = metrics.find((element) => (String(element.year) === String(year)) && (String(element.month) === String(month)) && (String(element.day) === String(day)));

      // Package metric findings.
      display_metrics.push({
        'year': String(next_date_month.getUTCFullYear()),
        'month': next_date_month.toLocaleString('default', { month: 'short' }),
        'day': day,
        'value': prepare_display_metrics_values(metric_type, (matched_metric && matched_metric['total']) ? parseInt(matched_metric['total']) : 0)
      });
    }

    return display_metrics;
  }

  function prepare_display_metrics_values( metric_type, value ) {
    let units = {
      'year': (24 * 60) * 365,
      'month': (24 * 60) * 30,
      'week': (24 * 60) * 7,
      'day': 24 * 60,
      'minute': 1
    }

    switch (metric_type) {
      case 'minutes':
        return parseFloat((value / units.year).toFixed(2));
      default:
        return value;
    }
  }

});
