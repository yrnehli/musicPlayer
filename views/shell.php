<!DOCTYPE html>
<html>
	<head>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
		<script src="https://code.jquery.com/jquery-3.4.1.js" integrity="sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
		<script src="https://kit.fontawesome.com/8562f52657.js" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.1/howler.min.js"></script>
		<script src="/assets/js/MusicPlayer.js"></script>
		<script src="/assets/js/global.js"></script>
		<link rel="stylesheet" href="/assets/css/global.css">
		<title>Music</title>
	</head>
	<body>
		<div id="partial">
			<?= $partial ?>
		</div>
		<?= $musicControl ?>
		<script>
			var $partial = $('#partial');

			$(function() {
				$(window).on('popstate', e => {
					var oState = e.originalEvent.state;

					if (oState) {
						$partial.html(oState.html);
					}
				});
			});

			async function loadPartial(url) {
				if (!loadPartial.hasBeenCalled) {
					history.pushState(getCurrentState(), "", document.URL);
					loadPartial.hasBeenCalled = true;
				}

				var res = await $.get(url, { partial: true });

				$partial.html(res);
				history.pushState(getCurrentState(), "", url);
			}

			loadPartial.hasBeenCalled = false;

			function getCurrentState() {
				return { html: $partial.html() };
			}
		</script>
	</body>
</html>