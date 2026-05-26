/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

const TOKEN_KEY = 'access_token';
const REFRESH_TOKEN_KEY = 'refresh_token';
const USERNAME_KEY = 'username';

function getToken() {
  return wx.getStorageSync(TOKEN_KEY) || null;
}

function getRefreshToken() {
  return wx.getStorageSync(REFRESH_TOKEN_KEY) || null;
}

function getUsername() {
  return wx.getStorageSync(USERNAME_KEY) || '';
}

function saveLogin(token, refreshToken, username) {
  wx.setStorageSync(TOKEN_KEY, token);
  wx.setStorageSync(REFRESH_TOKEN_KEY, refreshToken);
  wx.setStorageSync(USERNAME_KEY, username);
}

function clearToken() {
  wx.removeStorageSync(TOKEN_KEY);
  wx.removeStorageSync(REFRESH_TOKEN_KEY);
  wx.removeStorageSync(USERNAME_KEY);
}

function isLoggedIn() {
  const token = getToken();
  return token && token.length > 0;
}

module.exports = {
  getToken,
  getRefreshToken,
  getUsername,
  saveLogin,
  clearToken,
  isLoggedIn
};
