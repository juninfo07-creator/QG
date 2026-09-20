function urlBase64ToUint8Array(base64String) {
  var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  var rawData = window.atob(base64);
  var outputArray = new Uint8Array(rawData.length);
  for (var i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
  return outputArray;
}

function estaStandalone() {
  return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

function ehIOS() {
  return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
}

function suportaPush() {
  return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
}

function registrarServiceWorker() {
  if (!('serviceWorker' in navigator)) return Promise.reject('sem suporte');
  return navigator.serviceWorker.register('sw.js?v=1');
}

// Retorna: 'unsupported' | 'precisa-instalar' | 'default' | 'granted' | 'denied'
function statusNotificacoes() {
  if (!suportaPush()) return Promise.resolve('unsupported');
  if (ehIOS() && !estaStandalone()) return Promise.resolve('precisa-instalar');
  return Promise.resolve(Notification.permission);
}

function ativarNotificacoes() {
  return registrarServiceWorker()
    .then(function (reg) { return reg.pushManager.getSubscription().then(function (sub) { return { reg: reg, sub: sub }; }); })
    .then(function (r) {
      if (r.sub) return r.sub;
      return Notification.requestPermission().then(function (permissao) {
        if (permissao !== 'granted') throw new Error('permissao-negada');
        return fetch('../api/push-vapid-key.php')
          .then(function (res) { return res.text(); })
          .then(function (chave) {
            return r.reg.pushManager.subscribe({
              userVisibleOnly: true,
              applicationServerKey: urlBase64ToUint8Array(chave),
            });
          });
      });
    })
    .then(function (sub) {
      return fetch('../api/push-subscribe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(sub),
      }).then(function () { return sub; });
    });
}

function desativarNotificacoes() {
  return registrarServiceWorker()
    .then(function (reg) { return reg.pushManager.getSubscription(); })
    .then(function (sub) {
      if (!sub) return;
      var endpoint = sub.endpoint;
      return sub.unsubscribe().then(function () {
        return fetch('../api/push-unsubscribe.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ endpoint: endpoint }),
        });
      });
    });
}
