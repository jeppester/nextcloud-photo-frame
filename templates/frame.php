<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html>

<head>
  <style>
    :root {
      background-color: #222;
    }

    :root,
    body {
      margin: 0;
    }

    @keyframes fade-in {
      from {
        opacity: 0;
      }

      to {
        opacity: 100;
      }
    }

    .photoFrame {
      animation: fade-in 2s ease-in-out;
      position: absolute;
      width: 100vw;
      height: 100vh;
      background-position: center center;
      background-repeat: no-repeat;
      background-size: contain;
    }
  </style>

  <script type="text/javascript" defer nonce="<?php echo $_['cspNonce']; ?>">
    const expiry = new Date("<?php echo $_['expiresAt']; ?>")
    const imageUrl = `${location.href}/image`
    const refreshInterval = 1000 * 60 // Check if expired every minutes

    async function updateImage() {
      const now = new Date();

      // Always set new timeout so that the frame is resilient to network errors from here and down
      setTimeout(updateImage, refreshInterval)

      if (now > expiry) {
        const response = await fetch(imageUrl, { method: "HEAD", cache: "reload" })
        const nextExpiresAt = new Date(response.headers.get('expires'))

        const isNewImage = nextExpiresAt > now

        if (!isNewImage) return;

        const res = await fetch(imageUrl)
        const blob = await res.blob()
        // Read the Blob as DataURL using the FileReader API
        const reader = new FileReader();
        reader.onloadend = () => {
          const frame = document.querySelector('.photoFrame')
          const newFrame = document.createElement('div')
          newFrame.classList.add('photoFrame')
          newFrame.style.backgroundImage = `url('${reader.result}')`;
          document.body.appendChild(newFrame)

          // We cannot rely on the animation as it might not happen when the window is not focused
          setTimeout(() => {
            frame.remove()
          }, 2000);
        };
        reader.readAsDataURL(blob);
      }
    }

    setTimeout(updateImage, refreshInterval)
  </script>
</head>

<div class="photoFrame"
  style="background-image: url('/index.php/apps/photoframe/<?php echo $_['shareToken'] ?>/image')"></div>

</html>