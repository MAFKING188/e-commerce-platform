// Partner dashboard sales chart init (extracted from partner/dashboard.blade.php, Task 4.4).
// Chart data is passed from the blade via data attributes on the canvas element.
const salesCanvas = document.getElementById('salesChart');

if (salesCanvas) {
    const styles = getComputedStyle(document.documentElement);
    const accent = styles.getPropertyValue('--brand-accent').trim() || '#3b82f6';
    const textMuted = styles.getPropertyValue('--text-400').trim() || '#94a3b8';
    const grid = styles.getPropertyValue('--border').trim() || 'rgba(0,0,0,0.05)';

    const ctx = salesCanvas.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: JSON.parse(salesCanvas.dataset.labels),
            datasets: [{
                label: 'Daily Revenue ($)',
                data: JSON.parse(salesCanvas.dataset.values),
                borderColor: accent,
                backgroundColor: (context) => {
                    const gradient = ctx.createLinearGradient(0, 0, 0, context.chart.height);
                    gradient.addColorStop(0, accent + '26');
                    gradient.addColorStop(1, accent + '00');
                    return gradient;
                },
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointBackgroundColor: accent
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: styles.getPropertyValue('--surface-100').trim() || '#ffffff',
                    titleColor: styles.getPropertyValue('--text-900').trim() || '#0f172a',
                    bodyColor: styles.getPropertyValue('--text-600').trim() || '#475569',
                    borderColor: grid,
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: grid },
                    ticks: { color: textMuted, font: { weight: '600' }, padding: 8 }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: textMuted, font: { weight: '600' }, maxTicksLimit: 10 }
                }
            }
        }
    });
}