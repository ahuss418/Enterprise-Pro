document.addEventListener("DOMContentLoaded", () => {
  // Get stored font size and apply it
  const storedFontSize = localStorage.getItem("fontSize");
  if (storedFontSize) {
    document.documentElement.style.fontSize = storedFontSize;
    document.getElementById("font-size").value = storedFontSize;
  }

  // Listen for font size change
  document.getElementById("font-size").addEventListener("change", (event) => {
    const fontSize = event.target.value;

    // Apply to the entire document
    document.documentElement.style.fontSize = fontSize;

    // Save to local storage
    localStorage.setItem("fontSize", fontSize);
  });

  // Dark Mode
  const toggleButton = document.getElementById("toggle-dark-mode");
  const isDarkMode = localStorage.getItem("darkMode") === "true";

  // Apply dark mode on load
  if (isDarkMode) {
    document.body.classList.add("dark-mode");
    toggleButton.textContent = "Disable Dark Mode";
  }

  toggleButton.addEventListener("click", () => {
    const isDarkModeActive = document.body.classList.toggle("dark-mode");

    // Save dark mode state to local storage
    localStorage.setItem("darkMode", isDarkModeActive);
    toggleButton.textContent = isDarkModeActive
      ? "Disable Dark Mode"
      : "Enable Dark Mode";
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const savedTheme = localStorage.getItem("theme") || "light";
  document.body.classList.toggle("dark-mode", savedTheme === "dark");
});

function setTheme(theme) {
  document.body.classList.toggle("dark-mode", theme === "dark");
  localStorage.setItem("theme", theme);
}

function dropNav() {
  var dropdownMenu = document.querySelector(".dropNav");
  dropdownMenu.style.display =
    dropdownMenu.style.display === "none" ? "block" : "none";
}

document.addEventListener("DOMContentLoaded", () => {
  // Only run if the dropdown exists
  const dropdowns = document.querySelectorAll(".category-dropdown");

  if (dropdowns.length > 0) {
    console.log("Category dropdowns found. Binding events...");

    dropdowns.forEach((dropdown) => {
      dropdown.addEventListener("change", async (event) => {
        const assetId = event.target.getAttribute("data-asset-id");
        const categoryId = event.target.value;

        if (assetId) {
          try {
            const response = await fetch("/update-category.php", {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
              },
              body: JSON.stringify({ assetId, categoryId }),
            });

            const result = await response.json();

            if (result.success) {
              alert("Category updated successfully!");
            } else {
              alert("Failed to update category: " + result.error);
            }
          } catch (error) {
            console.error("Error:", error);
            alert("An error occurred while updating the category.");
          }
        }
      });
    });
  } else {
    console.log("No category dropdowns found — script not running.");
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const uploadPage = document.getElementById("upload-page");
  if (!uploadPage) return;

  const dropArea = document.getElementById("bulk-upload-drop-area");
  const fileInput = document.getElementById("bulk-upload-input");
  const fileTableBody = document.querySelector("#bulk-upload-table tbody");
  const uploadAllButton = document.getElementById("bulk-upload-all-button");

  let filesQueue = [];

  // Drag & drop events
  dropArea.addEventListener("dragover", (e) => {
    e.preventDefault();
    dropArea.classList.add("drag-over");
  });

  dropArea.addEventListener("dragleave", () => {
    dropArea.classList.remove("drag-over");
  });

  dropArea.addEventListener("drop", (e) => {
    e.preventDefault();
    dropArea.classList.remove("drag-over");
    handleFiles(e.dataTransfer.files);
  });

  dropArea.addEventListener("click", () => fileInput.click());

  fileInput.addEventListener("change", (e) => handleFiles(e.target.files));

  // Handle files (fixing file type issue)
  function handleFiles(fileList) {
    [...fileList].forEach((file) => {
      const fileExtension = file.name.split(".").pop().toLowerCase();

      if (fileExtension !== "csv") {
        alert(`Invalid file type: ${file.name}`);
        return;
      }

      // Avoid duplicate files
      if (filesQueue.some((f) => f.name === file.name)) {
        alert(`File already added: ${file.name}`);
        return;
      }

      filesQueue.push(file);
      updateFileTable();
    });
  }

  // Update file table
  function updateFileTable() {
    fileTableBody.innerHTML = "";

    filesQueue.forEach((file, index) => {
      const row = document.createElement("tr");
      row.innerHTML = `
              <td>${file.name}</td>
              <td id="status-${index}">Pending</td>
              <td>
                  <button onclick="removeFile(${index})" class="button button--danger button--small">Remove</button>
              </td>
          `;
      fileTableBody.appendChild(row);
    });

    uploadAllButton.disabled = filesQueue.length === 0;
  }

  // Remove file
  window.removeFile = (index) => {
    filesQueue.splice(index, 1);
    updateFileTable();
  };

  // Upload all files
  uploadAllButton.addEventListener("click", async () => {
    for (let i = 0; i < filesQueue.length; i++) {
      await uploadFile(filesQueue[i], i);
    }
  });

  // Upload file using FormData
  async function uploadFile(file, index) {
    const formData = new FormData();
    formData.append("csv_file", file);

    try {
      document.getElementById(`status-${index}`).textContent = "Uploading...";

      const response = await fetch("upload-handler.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        document.getElementById(`status-${index}`).textContent = "✅ Success";
      } else {
        document.getElementById(
          `status-${index}`
        ).textContent = `❌ Error: ${result.error}`;
      }
    } catch (error) {
      document.getElementById(`status-${index}`).textContent = "❌ Failed";
    }
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const qrcodeContainer = document.getElementById("qrcode");

  if (qrcodeContainer) {
    // Read the data-attribute for QR code value
    const qrCodeUrl = qrcodeContainer.getAttribute("data-url");

    if (qrCodeUrl) {
      new QRCode(qrcodeContainer, {
        text: qrCodeUrl,
        width: 150,
        height: 150,
      });
    }
  }
});
