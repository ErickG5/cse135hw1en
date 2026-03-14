document.addEventListener("DOMContentLoaded", () => {
    console.log("CSE 135 site loaded successfully");

    const year = new Date().getFullYear();
    const footer = document.createElement("p");
    footer.textContent = `${year} CSE 135`;
    footer.style.marginTop = "40px";
    footer.style.color = "#888";

    document.body.appendChild(footer);
});
