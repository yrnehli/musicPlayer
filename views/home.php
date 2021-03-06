yes

<script>
	$(function() {
		$('#partial').off('click').click(() => {
			loadPartial("/test");
		});
	});
</script>