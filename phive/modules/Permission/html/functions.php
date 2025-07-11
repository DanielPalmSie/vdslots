<?
function tag($tag)
{
	if (substr($tag, 0, 11) == 'pager.page.')
	{
		$page_id = substr($tag, 11, strlen($tag));
		$path = phive('Pager')->getPath($page_id);
		return $tag . ' (' . $path . ')';
	}
	else
		return $tag;
}
?>