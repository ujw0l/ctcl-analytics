document.addEventListener('DOMContentLoaded',()=>{



    const ctx = document.getElementById('myChart');
   
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ctclAnalyticsObject.data.map((x)=>x[0]),
      datasets: [{
        label: ctclAnalyticsObject.sales,
        data: ctclAnalyticsObject.data.map((x)=>x[1]),
        borderWidth: 1
      }]
    },
    options: {
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
});