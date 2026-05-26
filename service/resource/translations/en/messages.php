<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

/**
 * English business message translations
 * Covers all business module API error and success messages
 */
return [
    // ── Authentication ──
    'login_success' => 'Login successful',
    'register_success' => 'Registration successful',
    'logout_success' => 'Logged out',
    'password_changed' => 'Password changed successfully',
    'phone_changed' => 'Phone number changed successfully',
    'password_error' => 'Incorrect password',
    'old_password_error' => 'Old password is incorrect',
    'account_not_found' => 'Account not found',
    'account_disabled' => 'Account has been disabled',
    'token_expired' => 'Login expired, please log in again',
    'token_invalid' => 'Invalid login credential',
    'login_failed' => 'Login failed, please check credentials',

    // ── Verification Code ──
    'phone_required' => 'Please enter your phone number',
    'phone_invalid' => 'Please enter a valid phone number',
    'code_error' => 'Verification code is incorrect or expired',
    'code_sent' => 'Verification code sent',
    'code_send_failed' => 'Failed to send verification code',
    'code_send_too_frequent' => 'Code sent too frequently, please try again later',

    // ── Order ──
    'order_not_found' => 'Order not found',
    'order_created' => 'Order created successfully',
    'order_cancelled' => 'Order cancelled',
    'order_paid' => 'Order paid successfully',
    'order_confirmed' => 'Order confirmed',
    'order_completed' => 'Order completed',
    'order_refunding' => 'Refund request submitted',
    'refund_success' => 'Refund request submitted',
    'refund_failed' => 'Refund request failed',
    'verify_success' => 'Verification successful',
    'cannot_cancel' => 'Order cannot be cancelled in current status',
    'cannot_refund' => 'Order is not eligible for refund',
    'order_time_conflict' => 'This time slot is already booked, please choose another',

    // ── Technician ──
    'technician_locked' => 'Technician is locked for this time slot, please try again later',
    'technician_not_found' => 'Technician not found',
    'technician_offline' => 'Technician is offline',
    'technician_busy' => 'Technician is currently busy',
    'schedule_updated' => 'Schedule updated successfully',
    'withdraw_success' => 'Withdrawal request submitted',

    // ── Permission ──
    'permission_denied' => 'Permission denied',
    'not_technician' => 'Only technician accounts can perform this action',
    'role_switch_success' => 'Role switched successfully',

    // ── User ──
    'user_not_found' => 'User not found',
    'profile_updated' => 'Profile updated successfully',
    'nickname_too_long' => 'Nickname cannot exceed 50 characters',
    'gender_invalid' => 'Invalid gender value',
    'password_too_short' => 'Password must be at least 6 characters',
    'password_not_match' => 'Passwords do not match',
    'phone_duplicated' => 'This phone number is already linked to another account',
    'account_cancelled' => 'Account cancelled',

    // ── Address ──
    'address_created' => 'Address added successfully',
    'address_updated' => 'Address updated successfully',
    'address_deleted' => 'Address deleted',
    'address_not_found' => 'Address not found',

    // ── Favorites ──
    'favorite_added' => 'Added to favorites',
    'favorite_removed' => 'Removed from favorites',
    'favorite_exists' => 'Already in favorites',

    // ── Marketing / Coupons ──
    'coupon_received' => 'Coupon claimed successfully',
    'coupon_not_available' => 'Coupon is not available',
    'coupon_expired' => 'Coupon has expired',
    'card_buy_success' => 'Membership card purchased successfully',
    'card_not_found' => 'Membership card not found',
    'point_insufficient' => 'Insufficient points',
    'gift_card_redeemed' => 'Gift card redeemed successfully',
    'gift_card_invalid' => 'Gift card is invalid or already used',
    'check_in_success' => 'Check-in successful',
    'check_in_duplicated' => 'Already checked in today',

    // ── Store ──
    'store_not_found' => 'Store not found',
    'queue_number_taken' => 'Queue number taken successfully',
    'queue_already_waiting' => 'You are already in the queue',
    'queue_cancelled' => 'Queue cancelled',

    // ── Exam ──
    'exam_started' => 'Exam started',
    'exam_submitted' => 'Exam submitted',
    'exam_passed' => 'Exam passed',
    'exam_failed' => 'Exam not passed',
    'exam_not_found' => 'Exam not found',
    'exam_already_attempted' => 'You have already taken this exam',
    'exam_time_up' => 'Exam time is up',

    // ── Notification ──
    'notification_read' => 'Marked as read',
    'notification_all_read' => 'All marked as read',
    'device_registered' => 'Device registered successfully',
    'device_unregistered' => 'Device unregistered',

    // ── General ──
    'success' => 'Operation successful',
    'error' => 'Operation failed',
    'param_error' => 'Parameter error',
    'server_error' => 'Internal server error',
    'network_error' => 'Network error, please try again later',
    'data_not_found' => 'Data not found',
    'page_not_found' => 'Page not found',
    'too_many_requests' => 'Too many requests, please try again later',
    'maintenance_mode' => 'System under maintenance, please try again later',
    'version_outdated' => 'Client version is outdated, please update',
    'invalid_signature' => 'Invalid signature',
    'file_upload_failed' => 'File upload failed',
    'file_too_large' => 'File size exceeds limit',
    'file_type_not_allowed' => 'File type not allowed',
    'share_success' => 'Shared successfully',
    'referral_success' => 'Referral successful',
];
