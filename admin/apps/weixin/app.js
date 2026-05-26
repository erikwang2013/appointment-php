/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
const auth = require('./services/auth');

App({
  onLaunch() {
    const token = auth.getToken();
    if (token) {
      wx.switchTab({ url: '/pages/dashboard/dashboard' });
    }
  },

  globalData: {
    baseUrl: 'http://localhost:8787'
  }
});
