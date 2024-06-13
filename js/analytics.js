document.addEventListener('DOMContentLoaded',()=>{

/**
 * @since 1.0.0
 * 
 * Create chart based on data
 */

    const ctx = document.getElementById('myChart');
  
   if(null != ctx){ 
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


/**
 * 
 *  @since 1.0.0
 * 
 * Send REST Request
 */


document.querySelector('#ctcla-export-csv-submit').addEventListener('click', async(e)=>{


e.preventDefault();



 // Function to convert an array of objects to CSV format with custom headers
 function objectToCSV(objArray, headers = {}) {
  const keys = Object.keys(objArray[0]);
  const csvHeaders = keys.map(key => headers[key] || key);

  const array = [csvHeaders].concat(objArray.map(obj => 
      keys.map(key => 
          typeof obj[key] === 'string' ? JSON.stringify(obj[key]) : obj[key]
      )
  ));

  return array.map(row => row.join(',')).join('\n');
}


  // Function to trigger a download of the CSV file
  function downloadCSV(csvContent, filename) {
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}


// Custom headers mapping
const headers = {
  0: "Month",
 1: ctclAnalyticsObject.sales,

};

let obj  = ctclAnalyticsObject.data.map(x=>[x[0],Number(x[1]).toFixed(2)])

  // Convert the object array to CSV
  const csvContent = objectToCSV(obj,headers);


  downloadCSV(csvContent, 'Sales_Report.csv');
  
} );






}

});