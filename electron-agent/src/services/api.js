const axios=require('axios');
function createApi(baseURL){return axios.create({baseURL,timeout:10000,headers:{'Content-Type':'application/json'}});}
module.exports={createApi};
