<?php

class ProductTest extends PHPUnit_Framework_TestCase {

  /**
   * @var \Shopify\Client
   */
  private $client;

  public function setUp() {
    if (!getenv('SHOPIFY_ALLOW_TESTS')) {
      print 'Shopify tests cannot be run.' . PHP_EOL;
      print 'Running Shopify tests will delete all connected store info.' . PHP_EOL;
      print 'Set environment variable SHOPIFY_ALLOW_TESTS=TRUE to allow tests to be run.' . PHP_EOL;
      print PHP_EOL;
      exit;
    }
    $this->client = new Shopify\Client(getenv('SHOPIFY_SHOP_DOMAIN'), getenv('SHOPIFY_API_KEY'), getenv('SHOPIFY_PASSWORD'), getenv('SHOPIFY_SHARED_SECRET'));
  }

  public function testProductPost() {
    $product = [
      'title' => 'test product 1',
      'body_html' => 'test product <strong>body html</strong>',
    ];
    $product = $this->client->createProduct($product);
    $this->assertFalse($this->client->hasErrors(), 'Client has errors');
    $this->assertNotEmpty($product, 'Product response is empty');
    $this->productGet($product->id);
  }

  public function testBadProductPost() {
    $product = ['missing_title' => TRUE];
    try {
      $response = $this->client->createProduct($product);
    } catch (\Shopify\ClientException $e) {
      $this->assertEquals("can't be blank", $e->getErrors()->title[0]);
    }
  }

  public function testProductPut() {
    $product = [
      'title' => 'test product 2',
    ];
    $product = $this->client->createProduct($product);
    $update_product = ['title' => 'test product 2 UPDATED'];
    $product = $this->client->updateProduct($product->id, $update_product);
    $this->assertFalse($this->client->hasErrors(), 'Client has errors');
    $this->assertEquals('test product 2 UPDATED', $product->title, 'Product title is not updated');
  }

  private function productGet($id) {
    $product = $this->client->getProduct($id);
    $this->assertFalse($this->client->hasErrors());
    $this->assertNotEmpty($product, 'Product response is empty');
    $this->assertEquals('test product 1', $product->title, 'Product title does not match');
    $this->assertEquals('test product <strong>body html</strong>', $product->body_html, 'Product body_html does not match');
  }

  /**
   * @depends testProductPost
   */
  public function testProductDelete() {
    $response = $this->client->get('products', ['query' => ['fields' => 'id']]);
    foreach ($response->products as $product) {
      $response = $this->client->deleteProduct($product->id);
      $this->assertFalse($this->client->hasErrors(), 'Client has errors');
    }
    $this->assertEquals(0, $this->client->getProductsCount(), 'Not all products were deleted.');
  }

  /**
   * @depends testProductDelete
   */
  public function testAllProductsDeleted() {
    $response = $this->client->get('products', ['query' => ['fields' => 'id']]);
    $this->assertEmpty($response->products, 'Not all products were deleted');
  }

  /**
   * @depends testProductDelete
   */
  public function testProductPagination() {
    for ($i = 1; $i <= 3; $i++) {
      $this->client->createProduct(['title' => 'test product ' . $i]);
    }
    $counter = 1;
    foreach ($this->client->getResourcePager('products', 1) as $product) {
      $this->assertNotEmpty($product);
      $this->assertObjectHasAttribute('title', $product);
      $this->assertEquals('test product ' . $counter, $product->title);
      $counter++;
    }
    $this->assertEquals(3, $this->client->getProductsCount(), 'There should be 3 products in the system.');
    foreach ($this->client->getResourcePager('products', 3) as $product) {
      $this->assertObjectHasAttribute('id', $product);
      $this->client->deleteProduct($product->id);
    }
    $this->assertEquals(0, $this->client->getProductsCount(), 'Not all products were deleted.');
  }

}