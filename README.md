# photo_gallery
Photo Gallery plugin for WonderCMS

I needed a photo gallery in WonderCMS where I can upload photos easily and create albums. I began with the upload plugin and extended it with an input field where you can enter an album name. Then I made the part to display images on the screen and the ability to miove and delete these photos and albums.
I am not a professional programmer, therefore I now that a lot of things could be programmed better. I invite erverone to look at this program and make it better.

To use this photo gallery, download and extract the zip file, put the folder 'photo_galery' in the plugins folder of your WonderCMS website.
create a new page in you're WonderCMS website and add this to you're theme php file:

	<?php if (wCMS::$_currentPage == 'the name of the page'): ?>
		<div class="container">
			<div class="col-xs-12 col-sm-12">
				<div class="whiteBackground grayFont padding20 rounded5 marginTop20">
					<?php photo_gallery(); ?>
				</div>
			</div>
		</div>
	<?php endif ?>
