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

  /**
   * Multiple from addresses.
   *
   * The "primary address" i.e. the first determines the behaviour.
   * The other from addresses are subsituted with the site mail.
   */
  public function testMultipleHeaderFromAddresses() {
    $message['from'] = 'some@example.com, another@example.com';
    email_check_mail_alter($message);
    $this->assertEqual('some@example.com,another@example.com', $message['from']);
    $this->assertArrayNotHasKey('Reply-To', $message['headers']);
  }
  // treat as primary --> replyto is set
  public function testMultipleHeaderFromAddressesFirstSiteMail() {
    $message['from'] = 'site@example.com, another@example.com';
    email_check_mail_alter($message);
    $this->assertEqual('site@example.com,another@example.com', $message['from']);
    $this->assertEqual('reply-to@example.com', $message['headers']['Reply-To']);
  }
  public function testMultipleHeaderFromAddressesKeepOnlyInSiteDomain() {
    $message['from'] = 'another@example.com, some@other.com';
    email_check_mail_alter($message);
    $this->assertEqual('another@example.com,site@example.com', $message['from']);
    $this->assertEqual('some@other.com', $message['headers']['Reply-To']);
  }
  public function testMultipleHeaderFromAddressesFirstNotInDomainSetsReplyto() {
    $message['from'] = 'another@example.com, first@other.com, some@other.com';
    email_check_mail_alter($message);
    $this->assertEqual('another@example.com,site@example.com,site@example.com', $message['from']);
    $this->assertEqual('first@other.com', $message['headers']['Reply-To']);
  }

  /**
   * Reply-To only set (to any value) if sending from site mail.
   */
  public function testReplyToWhenFromSiteMail() {
    $message['from'] = 'site@example.com';
    email_check_mail_alter($message);
    $this->assertEqual('site@example.com', $message['from']);
    $this->assertEqual('reply-to@example.com', $message['headers']['Reply-To']);
  }
  public function testAnyReplyToWhenFromSiteMail() {
    $this->setConfig(['site_replyto_mail' => 'reply-to@other.com']);
    $message['from'] = 'site@example.com';
    email_check_mail_alter($message);
    $this->assertEqual('site@example.com', $message['from']);
    $this->assertEqual('reply-to@other.com', $message['headers']['Reply-To']);
  }
  public function testNoReplyToWhenFromSiteDomain() {
    $message['from'] = 'other@example.com';
    email_check_mail_alter($message);
    $this->assertEqual('other@example.com', $message['from']);
    $this->assertArrayNotHasKey('Reply-To', $message['headers']);
  }

  /**
   * Test the mail address matching regexp.
   */
  public function testSimpleFromAddressMatching() {
    $from_string = "simple@example.com";
    $array = _email_check_match_email_addresses($from_string);
    $this->assertEqual(array('simple@example.com'), $array);
  }
  public function testDoubleSimpleFromAddressMatching() {
    $from_string = "simple@example.com, second@example.com";
    $array = _email_check_match_email_addresses($from_string);
    $this->assertEqual(array('simple@example.com', 'second@example.com'), $array);
  }
  public function testUniqueSimpleFromAddressMatching() {
    $from_string = "simple@example.com, simple@example.com  ";
    $array = _email_check_match_email_addresses($from_string);
    $this->assertEqual(array('simple@example.com'), $array);
  }
  public function testDoubleSimpleFromAddressMatchingMultipleWhitespace() {
    $from_string = "simple@example.com ,  second@example.com";
    $array = _email_check_match_email_addresses($from_string);
    $this->assertEqual(array('simple@example.com', 'second@example.com'), $array);
  }
  public function testComplexFromAddressMatching() {
    $from_string = '"This is Complex" <complex@example.com> ';
    $array = _email_check_match_email_addresses($from_string);
    $this->assertEqual(array('"This is Complex" <complex@example.com>'), $array);
  }
  public function testComplexFromAddressMatchingWithCommaInName() {
    $from_string = '"Complex, this is" <complex@example.com> ';
    $array = _email_check_match_email_addresses($from_string);
    $this->assertEqual(array('"Complex, this is" <complex@example.com>'), $array);
  }
  public function testComplexFromAddressMatchingWithBracketsInName() {
    $from_string = '"Complex, this <aa> is" <complex@example.com> ';
    $array = _email_check_match_email_addresses($from_string);
    $this->assertEqual(array('"Complex, this <aa> is" <complex@example.com>'), $array);
  }
  public function testDoubleComplexFromAddressMatching() {
    $from_string = '"This is Complex" <complex@example.com>, Another <another@example.com>';
    $array = _email_check_match_email_addresses($from_string);
    $this->assertEqual(array('"This is Complex" <complex@example.com>', '<another@example.com>'), $array);
  }
  public function testSimpleFromAddressMatchingLongTld() {
    $from_string = 'simple@example.alongtld';
    $array = _email_check_match_email_addresses($from_string);
    $this->assertEqual(array('simple@example.alongtld'), $array);
  }
  public function testSimpleFromAddressMatchingNotMatchingUmlautInAddress() {
    $from_string = 'hellö@example.com';
    $array = _email_check_match_email_addresses($from_string);
    $this->assertEqual(array(), $array);
  }
  public function testSimpleFromAddressMatchingNotMatchingNameWhenMistakenQuote() {
    $from_string = 'A mistaken quote " in the text <simple@example.com>';
    $array = _email_check_match_email_addresses($from_string);
    $this->assertEqual(array('<simple@example.com>'), $array);
  }

  public function tearDown() {
    $GLOBALS['conf'] = $this->conf;
  }

}
