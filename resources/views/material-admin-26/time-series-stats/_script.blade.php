<script src="{{ asset('vendors/chart.js/chart.umd.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $([window, top.window]).blur(function() {
            toggleLoading( $('#btn-download-stats'), false );
        });

        Chart.defaults.font.family = 'Roboto, sans-serif';
        var ctx = document.getElementById('chart').getContext('2d');

        var primary_gradient = ctx.createLinearGradient(0, 0, 0, 400);
        primary_gradient.addColorStop(0, 'rgba(54, 162, 235,1)');
        primary_gradient.addColorStop(0.8, 'rgba(54, 162, 235,0)');

        var comparison_gradient = ctx.createLinearGradient(0, 0, 0, 400);
        comparison_gradient.addColorStop(0, 'rgba(255, 159, 64,1)');
        comparison_gradient.addColorStop(0.8, 'rgba(255, 159, 64,0)');

        var myChart = new Chart(ctx, {});

        $('#generate-graph').on('click', function (e) {
            e.preventDefault();
            var form = $('#stats-form');
            var data = getJsonFormData(form);
            var _this = this;

            data.format = 'chartjs';

            $.ajax({
                url: '{{ $apiUrl }}',
                type: 'GET',
                data: data,
                beforeSend: function () {
                    toggleLoading( $(_this), true );
                },
                complete: function () {
                    toggleLoading( $(_this), false );
                },
                success: function (response) {


                    var datasets = [];
                    var scales = {};

                    scales.x1 = {
                        display: true,
                        title: {
                            display: true,
                            text: response.date_range_title,
                            font: {
                                weight: '400'
                            },
                            color: '#545454',
                            padding: 15,
                        },
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#9f9f9f',
                            callback: function(value, index, values) {
                                var label = response.result.labels[index];
                                return label.split('#')[0];
                            }
                        }
                    };

                    if (response.compare_date_range) {
                        datasets.push({
                            label: response.aggregate_field_label + ' (Compared)',
                            data: response.result.compare,
                            lineTension: 0,
                            borderColor: 'rgb(255, 159, 64)',
                            backgroundColor: comparison_gradient,
                            fill: true,
                            xAxisID: 'x2',
                        });

                        scales.x2 = {
                            display: true,
                            //tipe: "time",
                            title: {
                                display: true,
                                text: response.compare_date_range_title,
                                font: {
                                    weight: '400'
                                },
                                color: '#545454',
                                padding: 15,
                            },
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#9f9f9f',
                                callback: function(value, index, values) {
                                    var label = response.result.labels[index];
                                    return label.split('#')[1];
                                }
                            }
                        };
                    }

                    datasets.push({
                        label: response.aggregate_field_label,
                        data: response.result.stats,
                        lineTension: 0,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: primary_gradient,//'rgba(54, 162, 235, 0.1)'
                        fill: true,
                        xAxisID: 'x1',
                    });

                    myChart.destroy();
                    scales.y = {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            borderColor: '#edf9fc',
                            borderWidth: 1,
                            tickColor: '#edf9fc',
                            color: '#edf9fc',
                        },
                        ticks: {
                            color: '#9f9f9f',
                            precision: 0,
                            callback: function(value, index, values) {
                                return value.toLocaleString('en-US');
                            }
                        }
                    };

                    myChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: response.result.labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            //aspectRatio: 3,
                            elements: {
                                point: {
                                    hoverRadius: 8
                                }
                            },
                            plugins: {
                                title: {
                                    display: true,
                                    text: response.metric_name,
                                    color: '#333',
                                    font: {
                                        weight: '400',
                                        size: 18
                                    }
                                },

                                legend: {
                                    reverse: true,
                                    labels: {
                                        color: '#545454',
                                        font: {
                                            weight: 'normal',
                                            size: 11
                                        },
                                        padding: 15,
                                        usePointStyle: true,
                                    },

                                },

                                tooltip: {
                                    mode: 'x',
                                    enabled: true,
                                    backgroundColor: 'rgb(255,255,255)',
                                    titleColor: '#747a80',
                                    bodyColor: '#747a80',
                                    borderColor: '#f1f1f1',
                                    borderWidth: 1,
                                    padding: 12,
                                    cornerRadius: 2,
                                    usePointStyle: true,
                                    caretSize: 0,
                                    callbacks: {
                                        label: function (context) {
                                            var label = context.dataset.label || '';

                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.parsed.y !== null) {
                                                label += context.parsed.y.toLocaleString('en-US');
                                            }

                                            return ' ' + label;
                                        },
                                        title: function (context, object) {
                                            var index = context[0].dataIndex;

                                            var title = response.result.labels[index];
                                            return title.replace('#', ' / ');
                                        }
                                    }
                                },
                            },
                            scales: scales
                        }
                    });
                },
                error: function (xhr) {
                    if (xhr.status == 422) {
                        showValidationErrorMsg(xhr);
                    } else {
                        Swal.fire({
                            title: __('Error!'),
                            text: __('An error occurred while loading the data.'),
                            icon: 'error'
                        });
                    }
                }
            });
        }).trigger('click');
    })
</script>
