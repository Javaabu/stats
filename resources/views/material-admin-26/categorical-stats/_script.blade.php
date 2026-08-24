<script src="{{ asset('vendors/chart.js/chart.umd.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $([window, top.window]).blur(function () {
            toggleLoading($('#btn-download-stats'), false);
        });

        Chart.defaults.font.family = 'Roboto, sans-serif';

        var context = document.getElementById('categorical-chart').getContext('2d');
        var categorical_chart = new Chart(context, {});

        $('#generate-graph').on('click', function (event) {
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
                    var datasets = [{
                        label: response.aggregate_field_label,
                        data: response.result.stats,
                        backgroundColor: 'rgba(54, 162, 235, 0.65)',
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 1
                    }];

                    if (response.result.compare) {
                        datasets.push({
                            label: response.aggregate_field_label + ' ({{ __('Compared') }})',
                            data: response.result.compare,
                            backgroundColor: 'rgba(255, 159, 64, 0.65)',
                            borderColor: 'rgb(255, 159, 64)',
                            borderWidth: 1
                        });
                    }

                    categorical_chart.destroy();
                    categorical_chart = new Chart(context, {
                        type: 'bar',
                        data: {
                            labels: response.result.labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                title: {
                                    display: true,
                                    text: response.metric_name,
                                    color: '#333',
                                    font: { weight: '400', size: 18 }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            return ' ' + context.dataset.label + ': ' + context.parsed.y.toLocaleString('en-US');
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0,
                                        callback: function (value) {
                                            return value.toLocaleString('en-US');
                                        }
                                    }
                                }
                            }
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
