// import { url, setRouter } from "../router/router.js";
// setRouter();

// const logout = document.getElementById("logout");

// logout.onclick = async () => {
//     const response = await fetch(url + "/api/logout", {
//         headers: {
//             Accept: "application/json",
//             Authorization: "Bearer " + localStorage.getItem("token"),
//             'ngrok-skip-browser-warning': 'any'
//         },
//     });
//     if (response.ok) {
//         localStorage.clear();
//         window.location.href = "/index.html";

//     } else {
//         const json = await response.json();
//         console.log(json.errors);
//     }
// };

// educExpenses();
// heathExpenses();
// foodExpenses();
// entExpenses();
// miscExpenses();
// shopExpenses();
// transExpenses();
// utilExpenses();

// fetch( url+ '/api/barchart', {
//         method: 'GET',
//         headers: {
//             Authorization: "Bearer " + localStorage.getItem("token"),
//             Accept: 'application/json',
//             'ngrok-skip-browser-warning': 'any'
//         },
//     })
//     .then(response => {
//         if (!response.ok) {
//             throw new Error('Network response was not ok');
//         }
//         return response.json();
//     })
//     .then(data => {
//         // Use the 'data' received from the server directly
//         barChart(
//             data.mondayIncome, data.tuesdayIncome, data.wednesdayIncome, data.thurdsayIncome,
//             data.fridayIncome, data.saturdayIncome, data.sundayIncome,
//             data.mondayExpenses, data.tuesdayExpenses, data.wednesdayExpenses, data.thurdsayExpenses,
//             data.fridayExpenses, data.saturdayExpenses, data.sundayExpenses, data.mondayBudget, data
//             .tuesdayBudget,
//             data.wednesdayBudget, data.thursdayBudget, data.fridayBudget, data.saturdayBudget,
//             data.sundayBudget,
//         );
//     })
//     .catch(error => {
//         console.error('There was a problem with the fetch operation:', error);
//     });



// function utilExpenses() {
//     fetch( url + '/api/utilexpenses', {
//             method: 'GET',
//             headers: {
//                 Authorization: "Bearer " + localStorage.getItem("token"),
//                 Accept: 'application/json',
//                 'ngrok-skip-browser-warning': 'any'
//             },
//         })
//         .then(response => {
//             if (!response.ok) {
//                 throw new Error('Network response was not ok');
//             }
//             return response.json();
//         })
//         .then(data => {
//             document.querySelector('.utilCon').innerHTML =
//                 `<h3 class="fw-bold">&#8369;${data.util}</h3>`;
//         })
//         .catch(error => {
//             console.error('There was a problem with the fetch operation:', error);
//         });
// }

// function transExpenses() {
//     fetch( url + '/api/transexpenses', {
//             method: 'GET',
//             headers: {
//                 Authorization: "Bearer " + localStorage.getItem("token"),
//                 Accept: 'application/json',
//                 'ngrok-skip-browser-warning': 'any'
//             },
//         })
//         .then(response => {
//             if (!response.ok) {
//                 throw new Error('Network response was not ok');
//             }
//             return response.json();
//         })
//         .then(data => {
//             document.querySelector('.transCon').innerHTML =
//                 `<h3 class="fw-bold">&#8369;${data.trans}</h3>`;
//         })
//         .catch(error => {
//             console.error('There was a problem with the fetch operation:', error);
//         });
// }

// function shopExpenses() {
//     fetch(url + '/api/shopexpenses', {
//             method: 'GET',
//             headers: {
//                 Authorization: "Bearer " + localStorage.getItem("token"),
//                 Accept: 'application/json',
//                 'ngrok-skip-browser-warning': 'any'
//             },
//         })
//         .then(response => {
//             if (!response.ok) {
//                 throw new Error('Network response was not ok');
//             }
//             return response.json();
//         })
//         .then(data => {
//             document.querySelector('.shopCon').innerHTML =
//                 `<h3 class="fw-bold">&#8369;${data.shop}</h3>`;
//         })
//         .catch(error => {
//             console.error('There was a problem with the fetch operation:', error);
//         });
// }

// function miscExpenses() {
//     fetch(url + '/api/miscexpenses', {
//             method: 'GET',
//             headers: {
//                 Authorization: "Bearer " + localStorage.getItem("token"),
//                 Accept: 'application/json',
//                 'ngrok-skip-browser-warning': 'any'
//             },
//         })
//         .then(response => {
//             if (!response.ok) {
//                 throw new Error('Network response was not ok');
//             }
//             return response.json();
//         })
//         .then(data => {
//             document.querySelector('.miscCon').innerHTML =
//                 `<h3 class="fw-bold">&#8369;${data.misc}</h3>`;
//         })
//         .catch(error => {
//             console.error('There was a problem with the fetch operation:', error);
//         });
// }

// function heathExpenses() {
//     fetch(url + '/api/healthexpenses', {
//             method: 'GET',
//             headers: {
//                 Authorization: "Bearer " + localStorage.getItem("token"),
//                 Accept: 'application/json',
//                 'ngrok-skip-browser-warning': 'any'
//             },
//         })
//         .then(response => {
//             if (!response.ok) {
//                 throw new Error('Network response was not ok');
//             }
//             return response.json();
//         })
//         .then(data => {
//             document.querySelector('.healthCon').innerHTML =
//                 `<h3 class="fw-bold">&#8369;${data.health}</h3>`;
//         })
//         .catch(error => {
//             console.error('There was a problem with the fetch operation:', error);
//         });
// }

// function foodExpenses() {
//     fetch(url + '/api/foodexpenses', {
//             method: 'GET',
//             headers: {
//                 Authorization: "Bearer " + localStorage.getItem("token"),
//                 Accept: 'application/json',
//                 'ngrok-skip-browser-warning': 'any'
//             },
//         })
//         .then(response => {
//             if (!response.ok) {
//                 throw new Error('Network response was not ok');
//             }
//             return response.json();
//         })
//         .then(data => {
//             document.querySelector('.foodCon').innerHTML =
//                 `<h3 class="fw-bold">&#8369;${data.food}</h3>`;
//         })
//         .catch(error => {
//             console.error('There was a problem with the fetch operation:', error);
//         });
// }

// function entExpenses() {
//     fetch(url + '/api/entexpenses', {
//             method: 'GET',
//             headers: {
//                 Authorization: "Bearer " + localStorage.getItem("token"),
//                 Accept: 'application/json',
//                 'ngrok-skip-browser-warning': 'any'
//             },
//         })
//         .then(response => {
//             if (!response.ok) {
//                 throw new Error('Network response was not ok');
//             }
//             return response.json();
//         })
//         .then(data => {
//             document.querySelector('.entCon').innerHTML =
//                 `<h3 class="fw-bold">&#8369;${data.ent}</h3>`;
//         })
//         .catch(error => {
//             console.error('There was a problem with the fetch operation:', error);
//         });
// }

// function educExpenses() {
//     fetch(url + '/api/educexpenses', {
//             method: 'GET',
//             headers: {
//                 Authorization: "Bearer " + localStorage.getItem("token"),
//                 Accept: 'application/json',
//                 'ngrok-skip-browser-warning': 'any'
//             },
//         })
//         .then(response => {
//             if (!response.ok) {
//                 throw new Error('Network response was not ok');
//             }
//             return response.json();
//         })
//         .then(data => {
//             document.querySelector('.educCon').innerHTML =
//                 `<h3 class="fw-bold">&#8369;${data.educ}</h3>`;
//         })
//         .catch(error => {
//             console.error('There was a problem with the fetch operation:', error);
//         });
// }

// function barChart(mondayIncome, tuesdayIncome, wednesdayIncome, thurdsayIncome, fridayIncome,
//     saturdayIncome, sundayIncome, mondayExpenses, tuesdayExpenses, wednesdayExpenses, thurdsayExpenses,
//     fridayExpenses, saturdayExpenses, sundayExpenses, mondayBudget, tuesdayBudget, wednesdayBudget, thursdayBudget,
//     fridayBudget, saturdayBudget, sundayBudget) {
//     var ctx = document.getElementById('myChart').getContext('2d');
//     var myChart = new Chart(ctx, {
//         type: 'bar',
//         data: {
//             labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
//             datasets: [{
//                     label: 'Income',
//                     data: [mondayIncome, tuesdayIncome, wednesdayIncome, thurdsayIncome, fridayIncome,
//                         saturdayIncome, sundayIncome
//                     ],
//                     backgroundColor: '#70D380',
//                     borderColor: '#70D380',
//                     borderWidth: 1
//                 },
//                 {
//                     label: 'Spending',
//                     data: [mondayExpenses, tuesdayExpenses, wednesdayExpenses, thurdsayExpenses,
//                         fridayExpenses, saturdayExpenses, sundayExpenses
//                     ],
//                     backgroundColor: '#FEE19D',
//                     borderColor: '#FEE19D',
//                     borderWidth: 1
//                 },
//                 {
//                     label: 'Budget',
//                     data: [mondayBudget, tuesdayBudget, wednesdayBudget, thursdayBudget,
//                         fridayBudget, saturdayBudget, sundayBudget
//                     ],
//                     backgroundColor: '#e93eff',
//                     borderColor: '#e93eff',
//                     borderWidth: 1
//                 }
//             ]
//         },
//         options: {
//             plugins: {
//                 legend: false // Hide legend
//             },
//             scales: {
//                 x: {
//                     grid: {
//                         display: false
//                     }
//                 },
//                 y: {
//                     beginAtZero: true,
//                     grid: {
//                         display: false,

//                     }
//                 },


//             }
//         }
//     });
// }