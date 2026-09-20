self.addEventListener('install', function (event) {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function (event) {
  var dados = { title: 'Quarteto Gileade', body: 'Você tem um novo aviso.', url: 'painel.php' };
  if (event.data) {
    try { dados = Object.assign(dados, event.data.json()); } catch (e) {}
  }

  var opcoes = {
    body: dados.body,
    icon: 'icon-192.png',
    badge: 'icon-192.png',
    data: { url: dados.url || 'painel.php' },
  };

  event.waitUntil(self.registration.showNotification(dados.title, opcoes));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = (event.notification.data && event.notification.data.url) || 'painel.php';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      for (var i = 0; i < clientList.length; i++) {
        var client = clientList[i];
        if (client.url.indexOf(url) !== -1 && 'focus' in client) return client.focus();
      }
      if (self.clients.openWindow) return self.clients.openWindow(url);
    })
  );
});
