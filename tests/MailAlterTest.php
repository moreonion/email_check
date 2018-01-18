<?php

namespace Drupal\email_check;

/**
 * Test email_check_mail_alter().
 */
class MailAlterTest extends \DrupalUnitTestCase {

  public function setUp() {
    $this->conf = $GLOBALS['conf'];
    $GLOBALS['conf'] = [
      'site_mail_domain' => 'example.com',
      'site_mail' => 'site@example.com',
      'site_replyto_mail' => 'reply-to@example.com',
    ] + $GLOBALS['conf'];
  }

  protected function setConfig($config) {
    $GLOBALS['conf'] = $config + $GLOBALS['conf'];
  }

  /**
   * Replace From if it’s not from site_mail_domain and use it as Reply-To.
   */
  public function testReplacingFrom() {
    $message['from'] = 'from@other.com';
    email_check_mail_alter($message);
    $this->assertEqual('site@example.com', $message['from']);
    $this->assertEqual('from@other.com', $message['headers']['Reply-To']);
  }

  /**
   * Explicit Reply-To always wins.
   */
  public function testReplacingFromWithExplicitReplyTo() {
    $message['from'] = 'from@other.com';
    $message['headers']['Reply-To'] = 'explicit-reply-to@other.com';
    email_check_mail_alter($message);
    $this->assertEqual('site@example.com', $message['from']);
    $this->assertEqual('explicit-reply-to@other.com', $message['headers']['Reply-To']);
  }

  /**
   * No Reply-To when header From is in mail_domain.
   */
  public function testReplyToWhenFromInMaildomain() {
    $message['from'] = 'some@example.com';
    email_check_mail_alter($message);
    $this->assertEqual('some@example.com', $message['from']);
    $this->assertFalse(isset($message['headers']['Reply-To']));
  }

  /**
   * Don’t set the site-wide Reply-To if it’s empty.
   */
  public function testNoReplacingWithoutSiteWideReplyTo() {
    $this->setConfig(['site_replyto_mail' => '']);
    $message['from'] = 'some@example.com';
    email_check_mail_alter($message);
    $this->assertEqual('some@example.com', $message['from']);
    $this->assertArrayNotHasKey('Reply-To', $message['headers']);
  }

  /**
   * Explicit Reply-To wins.
   */
  public function testNoReplacingWithExplicitReplyTo() {
    $message['from'] = 'some@example.com';
    $message['headers']['Reply-To'] = 'explicit-reply-to@other.com';
    email_check_mail_alter($message);
    $this->assertEqual('some@example.com', $message['from']);
    $this->assertEqual('explicit-reply-to@other.com', $message['headers']['Reply-To']);
  }

  /**
   * Return-Path is set.
   */
  public function testSetReturnPath() {
    $this->setConfig(['site_mail_return_path' => 'bounce@example.com']);
    $message['from'] = 'some@example.com';
    email_check_mail_alter($message);
    $this->assertEqual('some@example.com', $message['from']);
    $this->assertEqual('bounce@example.com', $message['headers']['Return-Path']);
  }

  /**
   * Return-Path is only set when in mail domain.
   * Default is the site_mail.
   */
  public function testOnlyReturnPathFromMailDomain() {
    $message['from'] = 'some@example.com';
    $message['headers']['Return-Path'] = 'return@other.com';
    email_check_mail_alter($message);
    $this->assertEqual('some@example.com', $message['from']);
    $this->assertEqual('site@example.com', $message['headers']['Return-Path']);
  }

  /**
   * A valid return path is preserved.
   * Default is the site_mail.
   */
  public function testValidReturnPathPreserved() {
    $message['from'] = 'some@example.com';
    $message['headers']['Return-Path'] = 'return@example.com';
    email_check_mail_alter($message);
    $this->assertEqual('some@example.com', $message['from']);
    $this->assertEqual('return@example.com', $message['headers']['Return-Path']);
  }

  /**
   * Return-Path is not set when Return-Path and site_mail are not
   * in site_mail_domain. Any invalid Return-Path is unset.
   */
  public function testNoReturnPathWhenNotMailDomain() {
    $this->setConfig(['site_mail_return_path' => 'bounce@other.com']);
    $this->setConfig(['site_mail' => 'site@other.com']);
    $message['headers']['Return-Path'] = 'return@other.com';
    $message['from'] = 'some@example.com';
    email_check_mail_alter($message);
    $this->assertEqual('some@example.com', $message['from']);
    $this->assertArrayNotHasKey('Reply-To', $message['headers']);
  }

  public function tearDown() {
    $GLOBALS['conf'] = $this->conf;
  }

}
