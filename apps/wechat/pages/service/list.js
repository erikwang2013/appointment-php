const api = require('../../utils/api');

Page({
  data: {
    categories: [],
    activeCategory: 0,
    services: [],
    page: 1,
    hasMore: true,
    keyword: ''
  },
  onLoad(options) {
    this.loadCategories();
    if (options.categoryId) {
      this.setData({ activeCategory: parseInt(options.categoryId) });
    }
    this.loadServices();
  },
  loadCategories() {
    api.get('/service/categories').then(res => {
      this.setData({ categories: [{ id: 0, name: '全部' }, ...res.data] });
    }).catch(() => {});
  },
  loadServices() {
    const { activeCategory, page, keyword } = this.data;
    if (!this.data.hasMore && page > 1) return;
    const data = { page, limit: 10 };
    if (activeCategory > 0) data.categoryId = activeCategory;
    if (keyword) data.keyword = keyword;
    api.get('/service/items', data).then(res => {
      const list = res.data || [];
      this.setData({
        services: page === 1 ? list : [...this.data.services, ...list],
        hasMore: list.length >= 10
      });
    }).catch(() => {});
  },
  onCategoryTap(e) {
    const id = parseInt(e.currentTarget.dataset.id);
    this.setData({ activeCategory: id, page: 1, services: [] }, () => {
      this.loadServices();
    });
  },
  onSearch(e) {
    this.setData({ keyword: e.detail.value, page: 1, services: [] }, () => {
      this.loadServices();
    });
  },
  goDetail(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: '/pages/service/detail?id=' + id });
  },
  onReachBottom() {
    if (this.data.hasMore) {
      this.setData({ page: this.data.page + 1 }, () => {
        this.loadServices();
      });
    }
  },
  onPullDownRefresh() {
    this.setData({ page: 1, services: [] }, () => {
      this.loadServices();
      wx.stopPullDownRefresh();
    });
  }
});
