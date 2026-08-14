const api = require('../../utils/api');

Page({
  data: {
    categories: [],
    activeCategory: 0,
    services: [],
    page: 1,
    perPage: 10,
    hasMore: true,
    keyword: ''
  },
  onLoad(options) {
    this.loadCategories();
    if (options.categoryId) {
      this.setData({ activeCategory: options.categoryId });
    }
    this.loadServices();
  },
  loadCategories() {
    api.get('/service/categories').then(res => {
      this.setData({ categories: [{ id: 0, name: '全部' }, ...res.data] });
    }).catch(() => {});
  },
  loadServices() {
    if (this._loading) return;
    const { activeCategory, page, keyword, perPage } = this.data;
    if (!this.data.hasMore) return;
    // 契约对齐：服务端 ServiceController::items 读 page / per_page / category_id
    const data = { page, per_page: perPage };
    if (activeCategory && String(activeCategory) !== '0') data.category_id = activeCategory;
    if (keyword) data.keyword = keyword;
    this._loading = true;
    api.get('/service/items', data).then(res => {
      const list = res.data || [];
      // 服务端 paginate() 返回 meta.has_more，缺失时退回长度判断
      const hasMore = res.meta ? !!res.meta.has_more : list.length >= perPage;
      this.setData({
        services: page === 1 ? list : [...this.data.services, ...list],
        hasMore,
        page: hasMore ? page + 1 : page
      });
    }).catch(() => {
    }).finally(() => {
      this._loading = false;
    });
  },
  onCategoryTap(e) {
    // 分类 id 为 hashid 字符串，原样透传
    const id = e.currentTarget.dataset.id;
    this.setData({ activeCategory: id, page: 1, services: [], hasMore: true }, () => {
      this.loadServices();
    });
  },
  onSearch(e) {
    this.setData({ keyword: e.detail.value, page: 1, services: [], hasMore: true }, () => {
      this.loadServices();
    });
  },
  goDetail(e) {
    // 服务项点击进入服务详情（item.id 为 hashid，原样透传）
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: '/pages/service/detail?id=' + id });
  },
  onReachBottom() {
    if (this.data.hasMore) {
      this.loadServices();
    }
  },
  onPullDownRefresh() {
    this.setData({ page: 1, services: [], hasMore: true }, () => {
      this.loadServices();
      wx.stopPullDownRefresh();
    });
  }
});
