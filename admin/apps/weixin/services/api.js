/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

const auth = require('./auth');

const BASE_URL = 'http://localhost:8787';

function request(method, path, data = {}, silent = false) {
  const token = auth.getToken();

  return new Promise((resolve, reject) => {
    wx.request({
      url: BASE_URL + path,
      method: method,
      data: data,
      header: {
        'Content-Type': 'application/json',
        'API-Version': 'v1',
        ...(token ? { 'Authorization': 'Bearer ' + token } : {})
      },
      success(res) {
        if (res.statusCode === 200) {
          const body = res.data;
          if (body.code === 0) {
            resolve(body);
          } else {
            if (body.code === 401 && !silent) {
              tryRefresh().then(refreshed => {
                if (refreshed) {
                  const newToken = auth.getToken();
                  wx.request({
                    url: BASE_URL + path,
                    method: method,
                    data: data,
                    header: {
                      'Content-Type': 'application/json',
                      'API-Version': 'v1',
                      'Authorization': 'Bearer ' + newToken
                    },
                    success(r2) {
                      if (r2.statusCode === 200 && r2.data.code === 0) {
                        resolve(r2.data);
                      } else {
                        auth.clearToken();
                        wx.reLaunch({ url: '/pages/login/login' });
                        reject(r2.data);
                      }
                    },
                    fail(err) {
                      reject(err);
                    }
                  });
                } else {
                  auth.clearToken();
                  wx.reLaunch({ url: '/pages/login/login' });
                  reject(body);
                }
              });
            } else {
              wx.showToast({ title: body.message || '请求失败', icon: 'none' });
              reject(body);
            }
          }
        } else {
          wx.showToast({ title: '服务器错误: ' + res.statusCode, icon: 'none' });
          reject(res);
        }
      },
      fail(err) {
        wx.showToast({ title: '网络请求失败', icon: 'none' });
        reject(err);
      }
    });
  });
}

function tryRefresh() {
  const refreshToken = auth.getRefreshToken();
  if (!refreshToken) return Promise.resolve(false);

  return new Promise((resolve) => {
    wx.request({
      url: BASE_URL + '/api/auth/refresh',
      method: 'POST',
      data: { refresh_token: refreshToken },
      header: { 'Content-Type': 'application/json' },
      success(res) {
        if (res.statusCode === 200 && res.data.code === 0) {
          const data = res.data.data;
          auth.saveLogin(
            data.access_token,
            data.refresh_token,
            data.user ? data.user.username : ''
          );
          resolve(true);
        } else {
          resolve(false);
        }
      },
      fail() {
        resolve(false);
      }
    });
  });
}

function get(path, params = {}) {
  const query = Object.keys(params)
    .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(params[k]))
    .join('&');
  const fullPath = query ? path + '?' + query : path;
  return request('GET', fullPath);
}

function post(path, data = {}) {
  return request('POST', path, data);
}

function put(path, data = {}) {
  return request('PUT', path, data);
}

function del(path, data = {}) {
  return request('DELETE', path, data);
}

module.exports = { get, post, put, del, request };
