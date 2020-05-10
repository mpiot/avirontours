import Chart from 'chart.js';
const $ = require('jquery');

$(function() {
    const logbookChartContainer = $('#logbookChart');

    const labels = logbookChartContainer.data('labels');
    const distances = logbookChartContainer.data('distances');
    const sessions = logbookChartContainer.data('sessions');

    let logbookChart = new Chart(logbookChartContainer, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    order: 1,
                    label: 'Distance',
                    yAxisID: 'distance',
                    data: distances,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                },
                {
                    order: 2,
                    type: 'line',
                    label: 'Nombre de séance',
                    yAxisID: 'sessions',
                    data: sessions,
                    borderColor: 'rgb(235,54,54, 0.5)',
                    fill: false,
                },
            ],
        },
        options: {
            scales: {
                yAxes: [
                    {
                        id: 'distance',
                        type: 'linear',
                        position: 'left',
                        ticks: {
                            beginAtZero: true
                        }
                    },
                    {
                        id: 'sessions',
                        type: 'linear',
                        position: 'right',
                        ticks: {
                            beginAtZero: true,
                            userCallback: function(label, index, labels) {
                                if (Math.floor(label) === label) {
                                    return label;
                                }

                            },
                        }
                    },
                ],
            },
        }
    });
});
