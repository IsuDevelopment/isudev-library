/**
 * The block renders in PHP and has no InnerBlocks. Returning null keeps
 * post_content free of markup and makes render.php the single source of
 * truth.
 *
 * @return {null} Nothing.
 */
export default function save() {
	return null;
}
