<?php
/**
 * Bulk scan session incomplete exception.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

/**
 * Thrown when bulk queue controls are requested before scan completion.
 */
final class BulkScanSessionIncompleteException extends \RuntimeException {
}
