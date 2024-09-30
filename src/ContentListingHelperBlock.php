<?php

namespace Drupal\ezcontent_block;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\views\ViewExecutableFactory;

/**
 * Helper class for ContentListingBlock.
 *
 * @package Drupal\ezcontent_block
 */
class ContentListingHelperBlock {

  // Define constants to avoid magic strings.
  const DEFAULT_TAG_ARGUMENT = 'all';
  const VIEW_NAME = 'article_content_listing';
  const DISPLAY_ID = 'block_1';

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The Executable view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $viewExecutable;

  /**
   * Content Listing constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity Type manager.
   * @param \Drupal\views\ViewExecutableFactory $viewExecutable
   *   A view executable instance, from the loaded entity.
   */
  public function __construct(EntityTypeManager $entityTypeManager, ViewExecutableFactory $viewExecutable) {
    $this->entityTypeManager = $entityTypeManager;
    $this->viewExecutable = $viewExecutable;
  }

  /**
   * Preprocess the Content Listing Block.
   *
   * @param object $block
   *   Block entity to preprocess.
   * @param string $type
   *   Type of response to be returned.
   * @param int $page
   *   The page number.
   *
   * @return array
   *   Preprocessed data for the given block.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getContentListingBlock($block, $type = '', $page = 0) {
    $arguments = $this->getArguments($block);
    $view = $this->getView();

    if (is_object($view)) {
      $view->setDisplay(self::DISPLAY_ID);
      $view->setArguments($arguments);

      return $this->executeView($view, $type, $page);
    }

    return [];
  }

  /**
   * Retrieve arguments for the view.
   *
   * @param object $block
   *   Block entity to preprocess.
   *
   * @return array
   *   Arguments for the view.
   */
  protected function getArguments($block) {
    $arguments = [];

    // Get tag entities.
    $tagsEntities = $block->hasField('field_tags') ? $block->field_tags->getString() : null;
    $arguments[] = str_replace(', ', '+', $tagsEntities) ?? self::DEFAULT_TAG_ARGUMENT;

    // Get author entities.
    $authorEntities = $block->hasField('field_author') ? $block->field_author->getString() : null;
    $arguments[] = str_replace(', ', '+', $authorEntities) ?? self::DEFAULT_TAG_ARGUMENT;

    return $arguments;
  }

  /**
   * Retrieve the view object.
   *
   * @return \Drupal\views\ViewExecutable|null
   *   The view object or null if not found.
   */
  protected function getView() {
    $viewObject = $this->entityTypeManager->getStorage('view')->load(self::VIEW_NAME);
    return $this->viewExecutable->get($viewObject);
  }

  /**
   * Execute the view and return the results.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   * @param string $type
   *   Type of response to be returned.
   * @param int $page
   *   The page number.
   *
   * @return array
   *   Preprocessed data for the given block.
   */
  protected function executeView($view, $type, $page) {
    if ($type === 'result') {
      $view->setCurrentPage($page);
      $view->execute();

      // Handle pager in case of JSON:API response.
      return [
        'rows' => $view->result,
        'total_rows' => $view->total_rows,
        'item_per_page' => $view->getItemsPerPage(),
      ];
    }

    $view->execute();
    return [
      'rows' => $view->buildRenderable(self::DISPLAY_ID),
    ];
  }
}
