function renderForm() {
  const form = document.getElementById("procedureForm");
  const selected = document.getElementById("procedure").value;
  form.innerHTML = "";

  const inputFields = {
    1: [
      "Donor ID",
      "Name",
      "Blood Group",
      "Last Donation Date (YYYY-MM-DD)",
      "Phone",
      "City",
    ],
    2: [
      "Patient ID",
      "Name",
      "Blood Group",
      "Diagnosis",
      "Phone",
      "Hospital ID",
    ],
    3: [
      "Donation ID",
      "Donor ID",
      "Date (YYYY-MM-DD)",
      "Volume (in liters)",
      "Blood Group",
      "Inventory ID",
    ],
    4: [
      "Request ID",
      "Patient ID",
      "Inventory ID",
      "Date (YYYY-MM-DD)",
      "Volume",
    ],
    5: [],
    6: ["Hospital ID"],
    7: [],
    8: [],
    9: ["Donor ID"],
    10: ["Blood Group"],
  };

  const params = inputFields[selected] || [];

  params.forEach((label, idx) => {
    const input = document.createElement("input");
    input.type = "text";
    input.name = `param${idx + 1}`;
    input.placeholder = label;
    form.appendChild(input);
  });

  if (selected) {
    const btn = document.createElement("button");
    btn.type = "submit";
    btn.innerText = "Simulate Procedure";
    form.appendChild(btn);
  }
}

function handleSubmit(event) {
  event.preventDefault();
  console.log("Submit event fired");

  const form = event.target;
  const inputs = [...form.querySelectorAll("input")].map(
    (input) => input.value
  );
  const proc = document.getElementById("procedure").value;

  // Basic validation
  if (inputs.some((val) => val.trim() === "")) {
    document.getElementById("output").innerText = "❌ All fields are required";
    return;
  }

  const postData = {
    procedure: proc,
    params: inputs,
  };
  console.log("Sending to PHP:", postData);

  fetch("execute_procedure.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(postData),
  })
    .then((res) => res.text())
    .then((data) => {
      console.log("📦 Server Response:", data);
      document.getElementById("output").innerText = data;
    })

    .catch((err) => {
      console.error("Fetch error:", err);
      document.getElementById("output").innerText =
        "❌ Error connecting to server.";
    });
}
