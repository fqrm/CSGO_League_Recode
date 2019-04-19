const electron = require('electron');
const app = electron.app;
const BrowserWindow = electron.BrowserWindow;
const ipc = electron.ipcMain;

//var mainWindow = null;
app.on('window-all-closed', function() {
    if (process.platform != 'darwin') {
      app.quit();
    }
  });
app.on('ready', function() {
    mainWindow = new BrowserWindow({width: 1280, height: 1024,frame: false});
    mainWindow.loadURL('file://' + __dirname + '/index.html');
    mainWindow.openDevTools();
    mainWindow.on('closed', function() {
      mainWindow = null;
    });
});
ipc.on('window-close',function() {
    console.log('mainWindow close');
    mainWindow.close();
})