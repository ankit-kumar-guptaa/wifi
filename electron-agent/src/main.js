const {app,BrowserWindow,Tray,Menu,nativeImage}=require('electron');
const os=require('os');
const axios=require('axios');
const path=require('path');

const API_BASE_URL=process.env.API_BASE_URL||'http://localhost:8000/api';
const HEARTBEAT_INTERVAL_MS=Number(process.env.HEARTBEAT_INTERVAL_MS||60000);
let mainWindow,tray,timer;

function createWindow(){
 mainWindow=new BrowserWindow({
  width:460,height:360,show:true,
  webPreferences:{contextIsolation:true,nodeIntegration:false,preload:path.join(__dirname,'preload.js')}
 });
 mainWindow.loadFile(path.join(__dirname,'renderer','index.html'));
 mainWindow.on('close',e=>{if(!app.isQuitting){e.preventDefault();mainWindow.hide();}});
}

async function heartbeat(){
 const payload={device_id:os.hostname(),hostname:os.hostname(),os_name:process.platform+'-'+os.release(),app_version:app.getVersion()};
 try{await axios.post(API_BASE_URL+'/devices/heartbeat',payload,{timeout:10000});}catch{}
}

app.whenReady().then(async()=>{
 createWindow();
 tray=new Tray(nativeImage.createEmpty());
 tray.setToolTip('WiFi Device Manager');
 tray.setContextMenu(Menu.buildFromTemplate([
  {label:'Open',click:()=>mainWindow.show()},
  {label:'Quit',click:()=>{app.isQuitting=true;app.quit();}}
 ]));
 await heartbeat();
 timer=setInterval(heartbeat,HEARTBEAT_INTERVAL_MS);
});
app.on('window-all-closed',e=>e.preventDefault());
app.on('before-quit',()=>clearInterval(timer));
