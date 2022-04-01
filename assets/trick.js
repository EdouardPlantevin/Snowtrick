function addVideo()
{
    var lastVideo = document.querySelectorAll(".video");

    lastVideo[lastVideo.length - 1].insertAdjacentHTML('afterend', `
                <div class="form-group"></span><label for="trick_video">Ajoute une vidéo</label><input type="text" name="video-${lastVideo.length}" class="video form-control form-control" required></div>
            `);

    let btnRemoveInput = document.querySelector(".remove-input-btn");
    btnRemoveInput.classList.remove('d-none')

}

function removeInputVideo()
{
    var lastVideo = document.querySelector(".video:last-child");

    if (lastVideo.id != 'trick_video') {
        lastVideo.parentNode.remove();
    } else {
        let btnRemoveInput = document.querySelector(".remove-input-btn");
        btnRemoveInput.classList.add('d-none')
    }
}

function removeImg(id)
{
    fetch('/trick/suppression-image/json', {
        method: 'post',
        headers: {
            'Content-type': 'application/json',
        },
        body: JSON.stringify({
            id: id
        }),
    })
        .then((response) => {
            return response.json();
        })
        .then((result) => {
            if (result.status === "success") {
                let imgDiv = document.querySelector("#image-" + id).parentNode
                imgDiv.style.display = "none"
            }
        })
}