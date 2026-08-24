<script src="{{ asset('vendors/chart.js/chart.umd.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $([window, top.window]).blur(function () {
            toggleLoading($('#categorical-download-stats'), false);
        });

        Chart.defaults.font.family = 'Roboto, sans-serif';

        var context = document.getElementById('categorical-chart').getContext('2d');
        var categorical_chart = new Chart(context, {});

        $('#categorical-generate-graph').on('click', function (event) {
            event.preventDefault();

            var form = $('#categorical-stats-form');
            var data = getJsonFormData(form);
            var button = this;

            data.format = 'chartjs';

            $.ajax({
                url: '{{ $apiUrl }}',
                type: 'GET',
                data: data,
                beforeSend: function () {
                    toggleLoading($(button), true);
                },
                complete: function () {
                    toggleLoading($(button), false);
                },
                success: function (response) {
                    var datasets = [];
                    var scales = {};
                    var date_range_title = response.date_range_title;

                    if (response.compare_date_range) {
                        date_range_title += ' / ' + response.compare_date_range_title;
                    }

                    scales.x = {
                        display: false,
                        ticks: {
                            display: false
                        }
                    };

                    scales.x1 = {
                        stacked: false,
                        display: true,
                        title: {
                            display: true,
                            text: date_range_title,
                            font: {
                                weight: '400'
                            },
                            color: '#545454',
                            padding: 15
                        },
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#9f9f9f',
                            callback: function (value, index) {
                                return response.result.labels[index];
                            }
                        }
                    };

                    if (response.compare_date_range) {
                        datasets.push({
                            label: response.aggregate_field_label + ' ({{ __('Compared') }})',
                            data: response.result.compare,
                            lineTension: 0,
                            borderColor: 'rgb(255, 159, 64)',
                            backgroundColor: 'rgba(255, 159, 64, 0.5)',
                            barThickness: 10,
                            fill: true,
                            xAxisID: 'x'
                        });

                        scales.x2 = {
                            stacked: false,
                            display: false,
                            title: {
                                display: false
                            },
                            grid: {
                                display: false
                            },
                            ticks: {
                                display: false
                            }
                        };
                    }

                    datasets.push({
                        label: response.aggregate_field_label,
                        data: response.result.stats,
                        lineTension: 0,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        barThickness: 10,
                        fill: true,
                        xAxisID: 'x'
                    });

                    categorical_chart.destroy();

                    scales.y = {
                        stacked: false,
                        beginAtZero: true,
                        grid: {
                            display: true,
                            borderColor: '#edf9fc',
                            borderWidth: 1,
                            tickColor: '#edf9fc',
                            color: '#edf9fc'
                        },
                        ticks: {
                            color: '#9f9f9f',
                            precision: 0,
                            callback: function (value) {
                                return value.toLocaleString('en-US');
                            }
                        }
                    };

                    categorical_chart = new Chart(context, {
                        type: 'bar',
                        data: {
                            labels: response.result.labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
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
                                    font: { weight: '400', size: 18 }
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
                                        usePointStyle: true
                                    }
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
                                        title: function (context) {
                                            var index = context[0].dataIndex;

                                            return response.result.labels[index];
                                        }
                                    }
                                }
                            },
                            scales: scales
                        }
                    });
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
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
    });
</script>
