// Frutiger Aero Dynamic UI & Realtime Polling Script
document.addEventListener('DOMContentLoaded', () => {
    console.log('Barbershop Aero Queue System Loaded.');

    // Auto refresh queue table every 10 seconds if on dashboard
    const queueContainer = document.getElementById('active-queue-list');
    if (queueContainer) {
        setInterval(fetchQueueStatus, 10000);
    }
});

function fetchQueueStatus() {
    fetch('../pelanggan/dashboard.php?ajax=1')
        .then(response => response.json())
        .then(data => {
            const queueList = document.getElementById('active-queue-list');
            if (queueList) {
                // Smooth refresh animation
                queueList.style.opacity = '0.6';
                setTimeout(() => {
                    queueList.innerHTML = data;
                    queueList.style.opacity = '1';
                }, 200);
            }
        })
        .catch(err => console.error('Error fetching live queue:', err));
}