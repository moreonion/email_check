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
   * Set site-wide Reply-To when none is set otherwise.
   */
  public function testNoReplacingWithSiteWideReplyTo() {
    $message['from'] = 'some@example.com';
    email_check_mail_alter($message);
    $this->assertEqual('some@example.com', $message['from']);
    $this->assertEqual('reply-to@example.com', $message['headers']['Reply-To']);
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

  public function tearDown() {
    $GLOBALS['conf'] = $this->conf;
  }

}
