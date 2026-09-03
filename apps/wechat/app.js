App({
  globalData: {
    userInfo: null,
    token: null,
    userType: 'customer',
    activeRole: 'customer',
    baseUrl: 'http://localhost:8788/api/v1',
    location: null
  },
  onLaunch() {
    const token = wx.getStorageSync('token');
    if (token) {
      this.globalData.token = token;
    }
  }
});
