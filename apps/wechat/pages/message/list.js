const api = require('../../utils/api');
Page({
  data: { messages: [] },
  onShow() { api.authGet('/notification').then(res => this.setData({ messages: res.data || [] })).catch(() => {}); },
  onRead(e) { api.put('/notification/read/' + e.currentTarget.dataset.id).then(() => this.onShow()); }
});
