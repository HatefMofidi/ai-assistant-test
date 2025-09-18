jQuery(document).ready(function($) {
    if (typeof walletChartData !== 'undefined' && typeof Chart !== 'undefined') {
        // نمودار نسبت مصرف به شارژ
        var usageCtx = document.getElementById('usageChart').getContext('2d');
        var usageChart = new Chart(usageCtx, {
            type: 'doughnut',
            data: {
                labels: ['مصرف شده', 'موجودی باقیمانده'],
                datasets: [{
                    data: [walletChartData.spent, walletChartData.remaining],
                    backgroundColor: ['#f44336', '#4caf50'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true
                    },
                    tooltip: {
                        rtl: true,
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.raw.toLocaleString();
                                return label + ': ' + value + ' تومان';
                            }
                        }
                    }
                }
            }
        });
        
        // نمودار توزیع موجودی
        var distCtx = document.getElementById('distributionChart').getContext('2d');
        var distChart = new Chart(distCtx, {
            type: 'bar',
            data: {
                labels: ['۰-۱۰,۰۰۰', '۱۰,۰۰۰-۵۰,۰۰۰', '۵۰,۰۰۰-۱۰۰,۰۰۰', '۱۰۰,۰۰۰-۵۰۰,۰۰۰', '۵۰۰,۰۰۰+'],
                datasets: [{
                    label: 'تعداد کاربران',
                    data: walletChartData.distribution,
                    backgroundColor: '#2196f3',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        rtl: true,
                        callbacks: {
                            label: function(context) {
                                return 'تعداد کاربران: ' + context.raw;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
});