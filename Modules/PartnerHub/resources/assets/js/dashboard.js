// Partner dashboard sales chart init (extracted from partner/dashboard.blade.php, Task 4.4).
// Chart data is passed from the blade via data attributes on the canvas element.
const salesCanvas = document.getElementById('salesChart');

if (salesCanvas) {
    const ctx = salesCanvas.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: JSON.parse(salesCanvas.dataset.labels),
            datasets: [{
                label: 'Daily Revenue ($)',
                data: JSON.parse(salesCanvas.dataset.values),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#3b82f6'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: { font: { weight: '600' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { weight: '600' } }
                }
            }
        }
    });
}