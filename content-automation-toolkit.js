//Loading Animation

var gifs = ["cat-1.gif", "cat-2.gif", "cat-3.gif", "cat-4.gif", "cat-5.gif", "cat-6.gif", "cat-7.gif", "cat-8.gif", "cat-9.gif", "cat-10.gif", "cat-11.gif"];
var catLoading = document.createElement("div");
catLoading.classList.add("catai-loading");
document.body.appendChild(catLoading);

document.querySelectorAll(".catai-generate-btn").forEach(function(btn) {
btn.addEventListener("click", function() {
catLoading.innerHTML = '<img src="' + plugin_path + '/cat-' + Math.floor(Math.random() * gifs.length) + '.gif" alt="Loading">';
catLoading.style.display = "block";
});
});

document.querySelectorAll(".catai-generate-btn").forEach(function(btn) {
btn.addEventListener("submit", function() {
catLoading.style.display = "none";
});
});

//FAQ

var faqQuestions = document.getElementsByClassName("catai-faq-question");
for (var i = 0; i < faqQuestions.length; i++) {
    faqQuestions[i].addEventListener("click", function() {
        this.classList.toggle("active");
        var answer = this.nextElementSibling;
        if (answer.style.maxHeight) {
            answer.style.maxHeight = null;
        } else {
            answer.style.maxHeight = answer.scrollHeight + "px";
        }
    });
}