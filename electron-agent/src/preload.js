const {contextBridge}=require('electron');
contextBridge.exposeInMainWorld('agent',{getStatus:()=>({status:'running'})});
