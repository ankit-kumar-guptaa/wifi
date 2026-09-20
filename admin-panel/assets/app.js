const API_BASE='http://localhost:8000/api';
let devices=[];
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
async function load(){
 try{const h=await fetch(API_BASE+'/health');const hd=await h.json();document.querySelector('#api').textContent=hd.success?'Online':'Error';}catch{document.querySelector('#api').textContent='Offline';}
 try{const r=await fetch(API_BASE+'/devices');const d=await r.json();devices=d.devices||[];}catch{devices=[];}
 render();
}
function render(){
 const online=devices.filter(x=>x.connection_status==='online').length;
 document.querySelector('#total').textContent=devices.length;
 document.querySelector('#online').textContent=online;
 document.querySelector('#offline').textContent=devices.length-online;
 const q=document.querySelector('#search').value.toLowerCase();
 const rows=devices.filter(x=>[x.device_id,x.hostname,x.os_name].join(' ').toLowerCase().includes(q));
 document.querySelector('#deviceRows').innerHTML=rows.length?rows.map(x=>'<tr><td>'+esc(x.device_id)+'</td><td>'+esc(x.hostname||'—')+'</td><td>'+esc(x.os_name||'—')+'</td><td><span class="status '+esc(x.connection_status||'offline')+'">'+esc(x.connection_status||'offline')+'</span></td><td>'+esc(x.last_heartbeat_at||'—')+'</td></tr>').join(''):'<tr><td colspan="5">No devices yet.</td></tr>';
}
document.querySelector('#refreshBtn').addEventListener('click',load);
document.querySelector('#search').addEventListener('input',render);
load();
