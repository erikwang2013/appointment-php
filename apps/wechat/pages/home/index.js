const api = require('../../utils/api');

Page({
  data: {
    banners: [],
    announcements: [],
    categories: [],
    currentBanner: 0
  },
  onLoad() {
    this.loadBanners();
    this.loadAnnouncements();
    this.loadCategories();
    this.getLocation();
  },
  loadBanners() {
    api.get('/content/banners').then(res => {
      this.setData({ banners: res.data || [] });
    }).catch(() => {});
  },
  loadAnnouncements() {
    api.get('/content/articles', { type: 'announcement' }).then(res => {
      this.setData({ announcements: res.data || [] });
    }).catch(() => {});
  },
  loadCategories() {
    api.get('/service/categories').then(res => {
      this.setData({ categories: res.data || [] });
    }).catch(() => {});
  },
  getLocation() {
    wx.getLocation({
      type: 'gcj02',
      success: (res) => {
        const app = getApp();
        app.globalData.location = {
          lat: res.latitude,
          lng: res.longitude
        };
      }
    });
  },
  onSwiperChange(e) {
    this.setData({ currentBanner: e.detail.current });
  },
  goToService(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: '/pages/service/list?categoryId=' + id });
  },
  goToCategory(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: '/pages/service/list?categoryId=' + id });
  }
});
