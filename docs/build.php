<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<title>GreenCape Extensions to Phing</title>
		<link rel="stylesheet" type="text/css" href="css/book.css">
	</head>
	<?php $contents = Helper::readContents(); ?>
	<body>
		<div id="top" class="book">
			<h1 class="title"><a name="app.greencape"></a>Addendum GC. GreenCape Extensions</h1>

			<p>This addendum contains a reference of all GreenCape extensions to Phing.</p>

			<p>This reference lists the tasks alphabetically by the name of the classes that implement
			the tasks. So if you are searching for the reference to the <code class="literal">&lt;joomla-download&gt;</code>
			tag, for example, you will want to look at the reference of
			<code class="literal">JoomlaDownloadTask</code>.</p>

			<dl class="toc">
				<dt><span xmlns:d="http://docbook.org/ns/docbook" class="appendix"><a href="#">GC. GreenCape Extensions</a></span></dt>
				<dd><dl><?php echo Helper::getTableOfContents($contents); ?></dl></dd>
			</dl>
			<?php foreach ($contents as $chapter) : ?>
				<?php echo $chapter['body']; ?>
				<a href="#top" style="float: right;">top</a>
			<?php endforeach; ?>
		</div>
	</body>
</html>
<?php

class Helper
{
	public static function readContents()
	{
		$chapters = glob(__DIR__ . '/chapters/*.html');
		sort($chapters);
		$contents = array();
		$i        = 0;
		foreach ($chapters as $chapter)
		{
			$contents[] = self::readChapter(++$i, $chapter);
		}

		return $contents;
	}

	public static function readChapter($i, $file)
	{
		$chapterNr = 'GC.' . $i;
		$content   = str_replace('@CHAPTER@', $chapterNr, file_get_contents($file));
		if (!preg_match('~<h2 class="title" style="clear: both"><a name="(.*?)"></a>(.*?)</h2>~sm', $content, $match))
		{
			echo "Unable to retrieve title from $file\n";
			$match = array(null, '', 'Unknown');
		}
		if (preg_match('~<body>(.*?)</body>~sm', $content, $body))
		{
			$content = $body[1];
		}

		return array(
			'name'  => $match[1],
			'title' => $match[2],
			'body'  => $content
		);
	}

	public static function getTableOfContents($contents)
	{
		$toc = '<ul>';
		foreach ($contents as $info)
		{
			$format = '<dt><span xmlns:d="http://docbook.org/ns/docbook" class="sect1"><a href="#%s">%s</a></span></dt>';
			$toc .= sprintf($format, $info['name'], $info['title']);
		}
		$toc .= '</ul>';

		return $toc;
	}
}
