<!DOCTYPE html>
<html>
	<head>
	</head>
	<body>
		<input onchange="upload(this)" type="file" name="image">
		<script>
			var uri = "//api.dev.fixxx.nl/afbeeldingen/";
			function upload(fileElement) {
				var file = fileElement.files[0];
				var placeholderElement = document.createElement("p");
				if (file.type === "image/jpeg" || file.type === "image/png") {
					placeholderElement.appendChild(document.createTextNode("uploading..."));
					fileElement.parentNode.replaceChild(placeholderElement, fileElement);
					var xhr = new XMLHttpRequest();
					xhr.open("POST", uri, true);
					xhr.setRequestHeader("X_FILENAME", file.name);
					xhr.send(file);
					xhr.onreadystatechange = function () {
						if (xhr.readyState === XMLHttpRequest.DONE) {
							placeholderElement.appendChild(document.createTextNode("upload ready"));
							var imgElement = document.createElement("img");
							imgElement.setAttribute("height", "200px");
							imgElement.setAttribute("src", uri + xhr.responseText);
							placeholderElement.appendChild(imgElement);
						}
					}
				}
			}
		</script>
	</body>
</html>
