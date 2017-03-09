<?php
defined('INC_ROOT') || die('Direct access is not allowed.');

wCMS::addListener('css', 'loadLBoxCSS');

function loadLBoxCSS($args) {
	array_push($args[0], '<link rel="stylesheet" href="'.wCMS::url("plugins/photo_gallery/lightbox/css/lightbox.min.css").'" type="text/css">', '<script src="'.wCMS::url("plugins/photo_gallery/lightbox/js/lightbox-plus-jquery.min.js").'"></script>');
	return $args;
}

wCMS::addListener('settings', 'photo_addHtmGalUploadForm');
wCMS::addListener('before', 'photo_GaluploadFile');

function photo_addHtmGalUploadForm ($args) {
	$output = $args[0];
	$remove = '<div class="padding20 toggle text-center" data-toggle="collapse" data-target="#settings">Close settings</div></div></div></div></div>';
	$output = substr($output, 0, -(strlen($remove)));
	$output .= '<div class=" marginTop20 change"><b style="font-size: 22px;" class="glyphicon glyphicon-info-sign" aria-hidden="true" data-toggle="tooltip" data-placement="right" title="Upload files from your device to your photo_gallery."></b>Upload files from your device to your photo_gallery<form action="' . wCMS::url(wCMS::$currentPage) . '" method="post" enctype="multipart/form-data"><div class="form-group"><input type="file" name="photo_galupfile" class="form-control"><input type="text" id="photo_galupfolder" name="photo_galupfolder" placeholder="Album Name" class="form-control"></div><button type="submit" class="btn btn-info">photo_gallery upload</button></form></div>' . $remove;
	$args[0] = $output;
	return $args;
}

function photo_GaluploadFile ($args) {
	
	if ( ! isset($_FILES['photo_galupfile'])) return;

	$_FILES['photo_galupfile']['name'] = str_replace(" ", "_", $_FILES['photo_galupfile']['name']); 

	$photo_imgphotobasic = INC_ROOT . "/photo_gallery";
	$photo_imgthumbbasic = $photo_imgphotobasic . "/thumbs";
	$photo_imgfolderbasic = $photo_imgphotobasic . "/folders";
	$photo_foldernumber = 1000;
	$photo_imgnumber = 1000;

	if ( ! is_dir($photo_imgphotobasic)) {
		mkdir($photo_imgphotobasic);
	}
	if ( ! is_dir($photo_imgthumbbasic)) {
		mkdir($photo_imgthumbbasic);
	}

	if ( ! is_dir($photo_imgfolderbasic)) {
		mkdir($photo_imgfolderbasic);
	}

	$photo_foldergalup = str_replace(" ","_",$_POST['photo_galupfolder']);

	$photo_imgsubready = 0;
	$photo_imgfolderbasic_glob = glob($photo_imgfolderbasic . "/*.jpg");
	sort($photo_imgfolderbasic_glob);
	$photo_imgfolderbasic_count = count($photo_imgfolderbasic_glob);
	if ($photo_imgfolderbasic_count > 0) {
		foreach ($photo_imgfolderbasic_glob as $photo_tempfolder) {
			$photo_tempbase = basename($photo_tempfolder);
			$photo_tempfolderstrip = strstr($photo_tempbase, '.', true);
			$photo_tempfoldernumber = substr($photo_tempfolderstrip,0,4);
			$photo_tempfoldercompare = substr($photo_tempfolderstrip,4);
			if ($photo_tempfoldercompare == $photo_foldergalup) {
				$photo_imgsubfolder = $photo_tempfoldercompare;
				$photo_foldernumber = $photo_tempfoldernumber; 
				$photo_imgsubready = 1;
			}
			$photo_tempfoldernumber = substr($photo_tempbase,0,4);
			if ($photo_tempfoldernumber > $photo_foldernumber && $photo_imgsubready != 1) {
				$photo_foldernumber = $photo_tempfoldernumber;
			}
		}
	}

	if ($photo_imgsubready != 1) {
		$photo_foldernumber++;
		$photo_imgsubfolder = $photo_foldergalup;
	}
	$photo_imgsubrepfolder = $photo_imgsubfolder;
	if(empty($photo_imgsubrepfolder)) {
		$photo_imgfolder = $photo_imgphotobasic;
	} else {
		$photo_imgfolder = $photo_imgphotobasic . "/" . $photo_imgsubrepfolder;
	}
	$photo_imgthumbfolder = $photo_imgfolder . "/thumbs";

	if (file_exists($photo_imgfolder . "/" . $_FILES['photo_galupfile']['name']))  return;

	/* find the highest used thumb number in the actual thumb folder */
	$photo_tmbimgfolderbasic_glob = glob($photo_imgthumbfolder . "/*.jpg");
	sort($photo_tmbimgfolderbasic_glob);
	$photo_tmbimgfolderbasic_count = count($photo_tmbimgfolderbasic_glob);
	if ($photo_tmbimgfolderbasic_count > 0) {
		foreach ($photo_tmbimgfolderbasic_glob as $photo_tmbtempfolder) {
			$photo_tmbtempbase = basename($photo_tmbtempfolder);
			$photo_imgnumber = substr($photo_tmbtempbase,0,4);
			if ($photo_tmbtempfoldernumber > $photo_imgnumber) {
				$photo_imgnumber = $photo_tmbtempfoldernumber;
			}
		}
	}

	$photo_imgnumber++;
	$photo_imgpath = $photo_imgfolder . "/" . $_FILES['photo_galupfile']['name'];
	$photoimgthumbpath = $photo_imgthumbfolder . "/" . $photo_imgnumber . $_FILES['photo_galupfile']['name'];

	$photo_allowed = [
		'jpg' => 'image/jpeg',
	];

	try {
		if (
			! isset($_FILES['photo_galupfile']['error']) ||
			is_array($_FILES['photo_galupfile']['error'])
		) {
			wCMS::alert('danger', '<strong>Upload</strong>: invalid parameters.');
			wCMS::redirect(wCMS::$currentPage);
		}

		switch ($_FILES['photo_galupfile']['error']) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
				wCMS::alert('danger', '<strong>Upload</strong>: no file sent.');
				wCMS::redirect(wCMS::$currentPage);
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				wCMS::alert('danger', '<strong>Upload</strong>: exceeded filesize limit.');
				wCMS::redirect(wCMS::$currentPage);
			default:
				wCMS::alert('danger', '<strong>Upload</strong>: unknown error.');
				wCMS::redirect(wCMS::$currentPage);
		}

		$photo_mimeType = '';
		if (class_exists('finfo')) {
			$photo_finfo = new finfo(FILEINFO_MIME_TYPE);
			$photo_mimeType = $photo_finfo->file($_FILES['photo_galupfile']['tmp_name']);
		} else if (function_exists('mime_content_type')) {
			$photo_mimeType = mime_content_type($_FILES['photo_galupfile']['tmp_name']);
		} else {
			$ext = strtolower(array_pop(explode('.', $_FILES['photo_galupfile']['name'])));
			if (array_key_exists($ext, $photo_allowed)) {
				$photo_mimeType = $photo_allowed[$ext];
			}
		}

		if (false === $ext = array_search(
			$photo_mimeType,
			$photo_allowed,
			true
		)) {
			wCMS::alert('danger', '<strong>Upload</strong>: invalid file format.');
			wCMS::redirect(wCMS::$currentPage);
		}

		if ( ! is_dir($photo_imgfolder)) {
			mkdir($photo_imgfolder);
		}

		if ( ! is_dir($photo_imgthumbfolder)) {
			mkdir($photo_imgthumbfolder);
			if(!empty($photo_imgsubrepfolder)) {
				$photo_foldervirtual_image = imagecreatetruecolor(250, 250);
				$photo_imgdata = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERGCEYGh0dHx8fExciJCIeJBweHx7/2wBDAQUFBQcGBw4ICA4eFBEUHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh7/wAARCAD6APoDAREAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD6NqCgoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKAAcnFAHm9/8RtQTW9Vs7Swsjb2V5JaI8m9mcx4DMcED724Y9qaQrjB8RdUxzp9hn/gf/wAVT5QuP/4WNqH/AEDLP/vtqLBccPiPeZ50m2x/11b/AApcoXHD4kXGedHhI9rg/wDxNHKFzq/CGujX9Nkujbi3eOYxtGH3dgQc4Hr+lJqwGzQMKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAbJNFbRPcznEUKGSQ+iqMn9BQB86eH3lm0iG6nH767LXUn+9Kxc/+hVZJeoAKACgAoA734QXGJ9SsyfvIkqj6Eg/+hCpkNHoVIYUAGD6UAGD6GgBdrf3T+VABsf8Aun8qAF2P/db8qAGkEdeKAEVlYkKytg4ODnFAC0AFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAc/8SZHi+HPiaSNyjjSbnBHUfu2oEeO2yqttEqjChFAHoMVYh9ABQAUAFAFrTb+902dp7C5e3lZChZOpUkHH6CgC63ibxCeus3n4SYpWQEbeIdebOda1Dn0uGH9aLIBh1vW2/5jOpHHpdP/AI0WQCHUNZkyDqGpP6/6Rif607ANabVjy0+oH3LvQBC8l1jEk8ox/flI/maAIXdMkvdQZP8AeuF/qaAImktR964tj/21U/yNAHVfD/xV4f8AD8F+L66YPO0ZUQxl+F3f/FUmho6KT4o+FF+61+/0t8fzNKzC5Vm+LPh5BlbPUHA6/Ko/rRYLne2sv2i1huBHJF5saybJBh1yM4YDuM80hklABQAUAFABQAUAFABQAUAFABQAUAFAGF8RV3/DvxOoXcTo13ge/kvQI8ZtTm1hPrGv8qsRJQBlXmryWutfY0giljW3EjbyeGJwBwfQUAKdbl/hs7Qf99n/ANmoAYdau/4YrZfpHn+ZNADTrN+ejwj6W6f1FADDq2oH/l4A/wB2NR/IUANOqaif+X2cf7rkfyoAa2oX7fevrpvrM3+NAELTzN96aRvq5NAEZAJyRk0AGB6UALQAUAFABQB0Pw50Ua74vs7SRN1tEftFx6eWmDg/U7V/GkwPohiSST1NSUJQAUAFABQAUAFABQAUAFABQAUAFABQBkeN13+CPEKZxu0q6Gf+2LUAeIacQdOtSOhhQ/8AjoqySegDknfz9Z1K46jzhCv0QY/nmgCSgAoAKACgAoAKACgAoAKACgAoAKACgD2f4HaN9j8PT6xKmJdQfbHkdIkJA/Nt35CpY0eg0hhQAUAFABQAUAFABQAUAFABQAUAFABQBneKVD+FdZQjIbTrgEev7pqAPB9HO7R7JvW2jP8A46KsksSyLDE8z/djUu30AzQBxukhv7Pjd+XkzIx9Sxz/AFoAt0AFABQAUAFABQAUAFABQAUAFABQBZ0mwn1TVLXTbb/XXUqxKcZxk8k+wGT+FAH0zZWsFjZQWNqu2C3jWKMeiqMCoKJqACgAoAKACgAoAKACgAoAKACgAoAKACgCprKl9F1BB1a0mA/74NAHz94fOdA045zm0i/9AFWSQ+K5TF4fugp+eVREvvuIH8iaAMhFCIqL0UACgBaACgAoAKACgAoAKACgAoAKACgAoA9J+BOj+fqt3rsqZjtF8iAn/no4+Yj6Lx/wOpkNHr9IYUAFABQAUAFABQAUAFABQAUAFABQAUAFAEV6oexuUPRoXB/FTQB8q6V4rsbTRbG2FtcySRW8aN91VyFAODk/yqySG6119bntrQWggjSYSk+ZuJ2g47CgC7QAUAFABQAUAFABQAUAFABQAUAFACHgdCfYd6APo/wPo39geFbHTWUCdU8y495W5b8vu/QCoYzaoGFABQAUAFABQAUAFABQAUAFABQAUAFABQAqqXOwDJYEAetAHw/bf8e8f+6Ksk2fDUebmWXH3Ux+Z/8ArUAb1ABQAUAFABQAUAFABQAUAFABQAUAdV8KdGGs+MrbzU3W1kPtU2eh2kbB+LEcegNJ7Aj34nJyakoKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAktf+PmP/eFAHw9GnloI8528Z9cVZJ0Xh2PbYs5/jc/kOP8aANOgAoAKACgAoAKACgAoAKACgAoAKAPcPgvo39neEzqEq4n1J/N5HIiXIQfj8zf8CFSxo7ikMKACgAoAKACgAoAKACgAoAKACgAoAKACgAoAltVYzoQDgMCTQB8R3ShLudAMBZXGPT5jVknT6dH5VhAmMEICfqef60AWKACgAoAKACgAoAKACgAoATIyBnknAHrQB0WjeCfFOrBWttHniiP/LW5/crj1G7BI+gNK6A7LSPhDkBta1nr1isk/wDZ3H/stK47HqUMUcEEcEKCOKJAkaDoqgYA/KkMfQAUAFABQAUAFABQAUAFABQAy6mhtY/Nup4reP8AvyuEH5mgDntQ8deE7LifWYp3H8NsrS5/FRt/WgDI/wCFq+GdxH2TWMD+LyI8H/yJmiwEw+J/hUqSTqIIH3TbDJ/8exRYDI1H4t24yNN0OaT0e6mCf+Orn+dFgOb1H4l+KrvIhntbFT2t4AT+b7j+WKdgOb1HV9W1Jv8AiY6neXYP8MszMo+gzgUAefCLzL3yR/FLt/WqJOt+nSgAoAKACgAoAKACgCS1gnu5xb2kE1zMekcKF2P4DmgDrdH+GvirUMNNaw6dEf4ruTDY/wBxctn64pXHY7PR/hNo9vtfVdQur9x1SICGM+3dj+YpXCx2ej6FoujAf2XpVpaMOPMSPMh+rnLH86QzRJJOSc0AFABQAUAFABQAUAFABQAUAYninXp9DhEseganqSbcl7ZVKJ7NyWH12496APOr/wCLWqzZXT9MsbUdMys0zD/0EfpTsBz2o+NvFd/kTa3cxqf4bfEIH/fAB/M0WAwJ3knlMs8jzSHq8jFifxNADaACgAoAKACgBR1oA5fS49+tt6Izt/MfzIqiToaACgAoAKALukaRqusPt0rTbq85wWijJUfVvuj8TQB2ej/CjXbna+p3dppqHqoPnSD8Fwv/AI9S5h2Oz0f4Y+F7Ha11FcanKO9xJtTPsq4H4HNK7Cx19ja2thB9nsbWC0h/55wRhF/IUhk1ABQAUAFABQAUAFABQAUAFABQAUAFAACQcg4oAqX2maZf5+3abZXRPUzW6ufzIoAxLzwF4RusltGSFj/FBK8ePwBx+lAHLa/4D8EWGTP4ml0xv7k08ch/BcBjRcDz/W7TRbWTbpWtyaoM/eNkYVx/wJs/pTAzKACgAoAKAAUAYmjx/wCn30p7SFB+ZJ/pVEmozBepA+tAG9o3g/xNq+1rLR7kRN/y1mHlJj1BfGfwzSugsdno/wAIpm2vrOsxxjvFZpuP/fbYA/75NK47HZ6N4E8KaXtaLSY7qUf8tbw+cfrg/KD9AKVwOlHCBFwFUYCjgAewoGFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFAGRceF/DVxK8s3h/THkc7nf7MoZj6kgdaAKz+CvCT/e0C0H+7uX+RoAryfD7wa/XRAp9Vuph/7PQBXk+G3hFvu2d1H/ALt0/wDUmgCCT4XeF2+7Jqcf+7cKf5qaLgV5PhPoB/1epasv+88bf+yCi4Fd/hLpp/1et3q/70KN/LFFwI9F+Dui2U0z32rX16skzSBERYRz2J+Yn8MU7isdtovhzQdGwdM0i0t5B0l2b5P++2y360gNUkk5JJoGFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQAUAFABQB//2Q==';
				$photo_imgdata = base64_decode($photo_imgdata);
				$photo_folderimgsource = imagecreatefromstring($photo_imgdata);
				imagejpeg($photo_folderimgsource, $photo_imgfolderbasic . "/" . $photo_foldernumber . $photo_imgsubrepfolder . ".jpg");
			 }
		}

		if ( ! move_uploaded_file(
			$_FILES['photo_galupfile']['tmp_name'],
			sprintf($photo_imgfolder . '/%s',
				$_FILES['photo_galupfile']['name']
			)
		)) {
			wCMS::alert('danger', '<strong>Upload</strong>: failed to move uploaded file.');
			wCMS::redirect(wCMS::$currentPage);
		}

		/* read the source image */
		list($photo_tmbwidth, $photo_tmbheight) = getimagesize($photo_imgpath);

		$photo_tmbverh = round(($photo_tmbwidth / $photo_tmbheight) , 2);
		if ($photo_tmbwidth < $photo_tmbheight) {$photo_tmbdesired_width = 250; $photo_tmbdesired_height = round((250 / $photo_tmbverh) , 0);
		} else { $photo_tmbdesired_height = 250; $photo_tmbdesired_width = round((250 * $photo_tmbverh), 0); }

		/* create a new, "virtual" image */
		$photo_tmbvirtual_image = imagecreatetruecolor($photo_tmbdesired_width, $photo_tmbdesired_height);
	
		/* convert original image */
		$photo_tmbsource = imagecreatefromjpeg($photo_imgpath);
	
		/* copy source image at a resized size */
		imagecopyresampled($photo_tmbvirtual_image, $photo_tmbsource, 0, 0, 0, 0, $photo_tmbdesired_width, $photo_tmbdesired_height, $photo_tmbwidth, $photo_tmbheight);
	
		/* create the physical thumbnail image to its destination */
		imagejpeg($photo_tmbvirtual_image, $photoimgthumbpath);


		wCMS::alert('success', '<strong>Upload</strong>: file uploaded successfully.');
		wCMS::redirect(wCMS::$currentPage);
	} catch (RuntimeException $e) {
		wCMS::alert('danger', '<strong>Upload</strong>: ' . $e->getMessage());
		wCMS::redirect(wCMS::$currentPage);
	}
}
?>

<?php
function photo_gallery() { ?>
	<div id="body">
	<?php

	$photo_memtxt = "plugins/photo_gallery/subfolder.txt";
	$photo_fdfolder_path = 'photo_gallery/folders/'; //folder path

	if (wCMS::$loggedIn) {
		echo '<br><br><form method="post">';
		echo '<input type="hidden" value=' . $photo_memtxt . ' name="photo_delete_subfolder_txt" />';
		echo '<h3><input type="submit" name ="backtomain" value="Back to the main album" /></h3>';echo '</form>';
	}


	if ( ! is_dir($photo_fdfolder_path)) {
		mkdir($photo_fdfolder_path);
	}



	$photo_fdgalfolderbasic_glob = glob($photo_fdfolder_path . "*.jpg");
	sort($photo_fdgalfolderbasic_glob);
	$photo_fdgalfolderbasic_count = count($photo_fdgalfolderbasic_glob);
	if ($photo_fdgalfolderbasic_count > 0)
	{
		for ($photo_fdfolder_x = 0; $photo_fdfolder_x < $photo_fdgalfolderbasic_count; $photo_fdfolder_x++)
		{
			$photo_fdgaltempfolder = $photo_fdgalfolderbasic_glob[$photo_fdfolder_x];
			$photo_fdfile = basename($photo_fdgaltempfolder);
			$photo_fdfiletp = substr($photo_fdfile, 4);
			$photo_fdfile_path = $photo_fdfolder_path.$photo_fdfile;
			$photo_fdtrimfdfile = strstr($photo_fdfiletp, '.', true);
			$photo_fdtrimreplace = str_replace("_"," ",$photo_fdtrimfdfile);
			$photo_fdextension = strtolower(pathinfo($photo_fdfile ,PATHINFO_EXTENSION));
			if($photo_fdextension=='jpg' || $photo_fdextension =='png' || $photo_fdextension == 'gif' || $photo_fdextension == 'bmp') 
			{
				?>
	            	<div style="float: left;">
					<div style="display: inline-block; width: 100px; height: 100px; overflow: hidden; margin-right: 5px;">
						<form method="post">
						<input type="image" name=<?php echo $photo_fdtrimfdfile; ?> src=<?php echo $photo_fdfile_path; ?> style="width: 100%;" />
						</form>
						<?php
						if(isset($_POST[$photo_fdtrimfdfile . '_x'])){
							$photo_retsubfolder=$photo_fdtrimfdfile;
							if (wCMS::$loggedIn) {
								$photo_mysubfolderfile = fopen($photo_memtxt, "w");
								fwrite($photo_mysubfolderfile, $photo_fdtrimfdfile);
								fclose($photo_mysubfolderfile);
							}
						}
						$photo_folder_path_n = 'photo_gallery/' . $photo_fdtrimfdfile . '/thumbs'; 
						$photo_image_path_n = 'photo_gallery/' . $photo_fdtrimfdfile;
						?>
					</div>
					<br />
					<div style="display: inline-block; width: 100px; height: 30px; overflow: hidden; margin-right: 5px;">
						<?php
						echo $photo_fdtrimreplace;
						?>
					</div>
					<br />
					<div style="text-align: center; width: 100px; height: auto; overflow: hidden; margin-right: 5px;">
					<?php
					if (wCMS::$loggedIn) {

						if ($photo_fdfolder_x > 0) {
							echo '<div style="display: inline-block;"><form method="post">';
							echo '<input type="hidden" value="-1" name="photo_move_direction" />';
							echo '<input type="hidden" value="'.$photo_fdfile.'" name="photo_move_folder" />';
							echo '<input type="hidden" value="'.$photo_fdfolder_path.'" name="photo_move_folder_path" />';
							echo '<input type="submit" name="movabmlft" value="<--" />';echo '</form></div>';
						}

						if ($photo_fdfolder_x < $photo_fdgalfolderbasic_count - 1) {
							echo '<div style="display: inline-block;"><form method="post">';
							echo '<input type="hidden" value="1" name="photo_move_direction" />';
							echo '<input type="hidden" value="'.$photo_fdfile.'" name="photo_move_folder" />';
							echo '<input type="hidden" value="'.$photo_fdfolder_path.'" name="photo_move_folder_path" />';
							echo '<input type="submit" name="movabmrgt" value="-->" />';echo '</form></div>';
						}

						echo '<br><br><form method="post">';
						echo '<input type="hidden" value="'.$photo_fdfile_path.'" name="photo_delete_folder_img" />';
						echo '<input type="hidden" value="'.$photo_folder_path_n.'" name="photo_delete_folder_thumb" />';
						echo '<input type="hidden" value="'.$photo_image_path_n.'" name="photo_delete_folder" />';
						echo '<input type="submit" name="delfolder" value="Delete folder" />';echo '</form>';
					}
					?>
					</div>
				</div>
				<?php
			}
		}
	}
	?>
	<p style="clear: both;"></p>
	</div>

<?php 

if (wCMS::$loggedIn) {
	if (file_exists($photo_memtxt)) {
		$photo_mysubfolderfile = fopen($photo_memtxt, "r");
		$photo_folder_path_nw = fread($photo_mysubfolderfile,filesize($photo_memtxt));
		fclose($photo_mysubfolderfile);
	}
	else
	{
	$photo_folder_path_nw = $photo_retsubfolder;
	}
}
else
{
	$photo_folder_path_nw = $photo_retsubfolder;
}

?>

<h3><b><?php $photo_folder_path_nw_strip = str_replace("_"," ",$photo_folder_path_nw);
echo $photo_folder_path_nw_strip; ?></b></h3>

	<div id="body">
	<?php
	if(empty($photo_folder_path_nw)) {
		$photo_folder_path = 'photo_gallery/thumbs/'; //thumbs folder path
		$photo_image_path = 'photo_gallery/'; //image's folder path
			if ( ! is_dir($photo_folder_path)) {
			mkdir($photo_folder_path);
		}
		if ( ! is_dir($photo_image_path)) {
			mkdir($photo_image_path);
		}
	}
	else
	{
		$photo_folder_path = 'photo_gallery/' . $photo_folder_path_nw . '/thumbs/'; //thumb folder path
		$photo_image_path = 'photo_gallery/' . $photo_folder_path_nw . '/';
	}
 

	$photo_galfolderbasic_glob = glob($photo_folder_path . "*.jpg");
	sort($photo_galfolderbasic_glob);
	$photo_galfolderbasic_count = count($photo_galfolderbasic_glob);
	if ($photo_galfolderbasic_count > 0)
	{
		for ($photo_folder_x = 0; $photo_folder_x < $photo_galfolderbasic_count; $photo_folder_x++)
		{
			$photo_galtempfolder = $photo_galfolderbasic_glob[$photo_folder_x];
			$photo_file = basename($photo_galtempfolder);
			$photo_filetp = substr($photo_file, 4);
			$photo_file_path = $photo_folder_path.$photo_file;
			$photo_trimfdfile = strstr($photo_filetp, '.', true);
			$photo_trimfdfile_n = strstr($photo_trimfdfile, '--', true);
			if ($photo_trimfdfile_n == '') { $photo_trimfdfile_n = $photo_trimfdfile; }
			$photo_trimreplace = str_replace("_"," ",$photo_trimfdfile_n);
			$photo_fileimg_path = $photo_image_path.$photo_filetp;
			$photo_extension = strtolower(pathinfo($photo_file ,PATHINFO_EXTENSION));
			if($photo_extension=='jpg' || $photo_extension =='png' || $photo_extension == 'gif' || $photo_extension == 'bmp') 
			{
				?>
	            	<div style="float: left;">
					<div style="display: inline-block; width: 208px; height: 208px; overflow: hidden; margin-right: 5px;">
						<?php
						list($photo_width, $photo_height) = getimagesize($photo_file_path);
						if ($photo_width > $photo_height) { $photo_verh = 0 - round(((($photo_width / $photo_height)*100)-100)/2 , 0) . "%"; ?>
							<a href="<?php echo $photo_fileimg_path; ?>" data-lightbox="allimages" data-title="<?php echo $photo_trimreplace; ?>"><img class="gal" src="<?php echo $photo_file_path; ?>"  style="height: 100%; margin-left: <?php echo $photo_verh ?>" /></a>
						<?php
						} else { $photo_verh = 0 - round(((($photo_height / $photo_width)*100)-100)/2 , 0) . "%"; ?>
							<a href="<?php echo $photo_fileimg_path; ?>" data-lightbox="allimages" data-title="<?php echo $photo_trimreplace; ?>"><img class="gal" src="<?php echo $photo_file_path; ?>"  style="width: 100%; margin-top: <?php echo $photo_verh ?>" /></a>
						<?php
						}
					?>
					</div>
					<br />
					<?php
						echo $photo_trimreplace;
					?>
					<br /><br />
					<div style="text-align: center; width: 208px; height: auto; overflow: hidden; margin-right: 5px;">
					<?php
					if (wCMS::$loggedIn) {

						if ($photo_folder_x > 0) {
							echo '<div style="display: inline-block;"><form method="post">';
							echo '<input type="hidden" value="-1" name="photo_move_direction" />';
							echo '<input type="hidden" value="'.$photo_file.'" name="photo_move_folder" />';
							echo '<input type="hidden" value="'.$photo_folder_path.'" name="photo_move_folder_path" />';
							echo '<input type="submit" name="movimglft" value="<--" />';echo '</form></div>';
						}

						if ($photo_folder_x < $photo_galfolderbasic_count - 1) {
							echo '<div style="display: inline-block;"><form method="post">';
							echo '<input type="hidden" value="1" name="photo_move_direction" />';
							echo '<input type="hidden" value="'.$photo_file.'" name="photo_move_folder" />';
							echo '<input type="hidden" value="'.$photo_folder_path.'" name="photo_move_folder_path" />';
							echo '<input type="submit" name="movimgrgt" value="-->" />';echo '</form></div>';
						}

						echo '<br><br><div style="padding: 0px 10px 10px 10px;"><form method="post">';
						echo '<input type="hidden" value="'.$photo_file_path.'" name="photo_delete_thumb" />';
						echo '<input type="hidden" value="'.$photo_fileimg_path.'" name="photo_delete_file" />';
						echo '<input type="submit" name="delimage" value="Delete image" />';echo '</form></div>';
					}
					?>
					</div>
				</div>
				<?php
			}
		}
	}
	?>
	<p style="clear: both;"></p>
	</div>
<?php } ?>

<?php
	if ($_POST['backtomain']) {
		$photo_filename = $_POST['photo_delete_subfolder_txt'];
		if (file_exists($photo_filename)) {
	 		unlink($photo_filename);
	  	}
	}
?>

<?php
	if ($_POST['delimage']) {
		$photo_filename = $_POST['photo_delete_thumb'];
		$photo_imagename = $_POST['photo_delete_file'];
		if (file_exists($photo_filename)) {
	 		unlink($photo_filename);
			wCMS::alert('success', '<strong>Delete image</strong>: file deleted successfully.');
	  	} else {
	    		echo 'Could not delete '.$photo_filename.', file does not exist';
	 	}
		if (file_exists($photo_imagename)) {
	 		unlink($photo_imagename);
	  	} else {
	    		echo 'Could not delete '.$photo_imagename.', file does not exist';
	 	}
	}
?>

<?php
	if ($_POST['delfolder']) {
		$photo_filename = $_POST['photo_delete_folder_img'];
		$photo_folderthumbname = $_POST['photo_delete_folder_thumb'];
		$photo_foldername = $_POST['photo_delete_folder'];

		if(!rmdir($photo_folderthumbname)) {
			wCMS::alert('danger', '<strong>Delete folder</strong>: Could not remove the folder, because the folder is not empty!!');
		} else {

			rmdir($photo_foldername);

			if (file_exists($photo_filename)) {
	 			unlink($photo_filename);
	    			echo 'File '.$photo_filename.' has been deleted, ';
	  		} else {
	    			echo 'Could not delete '.$photo_filename.', file does not exist';
	 		}
		}
	}
?>

<?php
	if ($_POST['movimgrgt'] || $_POST['movimglft'] || $_POST['movabmrgt'] || $_POST['movabmlft']) {
		$photo_fdprichting = $_POST['photo_move_direction'];
		$photo_movefilename = $_POST['photo_move_folder'];
		$photo_movefolderpath = $_POST['photo_move_folder_path'];

		$photo_mvr_number = substr($photo_movefilename,0,4);
		$photo_mvr_filename = substr($photo_movefilename,4);

		$photo_mvr_files = array();
		$photo_mvr_files_glob = glob($photo_movefolderpath . "*.jpg");
		if ($photo_fdprichting > 0) {
			sort($photo_mvr_files_glob);
		}
		else
		{
			rsort($photo_mvr_files_glob);
		}
		$photo_mvr_files_count = count($photo_mvr_files_glob);
		if ($photo_mvr_files_count > 0) {
			$photo_mvr_x_next = -1;
			for ($photo_mvr_x = 0; $photo_mvr_x < $photo_mvr_files_count; $photo_mvr_x++) {
				$photo_mvr_tempfile = $photo_mvr_files_glob[$photo_mvr_x];
				$photo_mvr_tempbase = basename($photo_mvr_tempfile);
				$photo_mvr_number_rep = substr($photo_mvr_tempbase,0,4);
				$photo_mvr_filename_rep = substr($photo_mvr_tempbase,4);
				if ($photo_mvr_number_rep == $photo_mvr_number) {
					$photo_mvr_x_next = $photo_mvr_x + 1;
				}
				if ($photo_mvr_x_next == $photo_mvr_x) {
					$photo_mvr_filename_ren = $photo_mvr_filename_rep;
					$photo_mvr_number_ren = $photo_mvr_number_rep;
				}
			}
		}
		rename($photo_movefolderpath . $photo_mvr_number . $photo_mvr_filename ,$photo_movefolderpath . $photo_mvr_number_ren . $photo_mvr_filename);
		rename($photo_movefolderpath . $photo_mvr_number_ren . $photo_mvr_filename_ren ,$photo_movefolderpath . $photo_mvr_number . $photo_mvr_filename_ren);
	}
?>



