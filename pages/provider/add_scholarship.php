<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("provider");

$provider_id = $_SESSION['user_id'];

$title = "";
$description = "";
$eligibility = "";
$required_documents = "";
$benefit = "";
$total_slots = "";
$location = "";
$deadline = "";
$status = "open";

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $eligibility = trim($_POST['eligibility'] ?? '');
    $required_documents = trim($_POST['required_documents'] ?? '');
    $benefit = trim($_POST['benefit'] ?? '');
    $total_slots = (int)($_POST['total_slots'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $deadline = trim($_POST['deadline'] ?? '');
    $status = trim($_POST['status'] ?? 'open');

    if ($title === '') {
        $errors[] = "Scholarship title is required.";
    }

    if ($description === '') {
        $errors[] = "Description is required.";
    }

    if ($benefit === '') {
        $errors[] = "Scholarship benefit is required.";
    }

    if ($location === '') {
        $errors[] = "Location / coverage is required.";
    }

    if ($eligibility === '') {
        $errors[] = "Eligibility requirements are required.";
    }

    if ($required_documents === '') {
        $errors[] = "Required documents are required.";
    }

    if ($total_slots <= 0) {
        $errors[] = "Total slots must be greater than 0.";
    }

    if ($deadline === '') {
        $errors[] = "Deadline is required.";
    } elseif (strtotime($deadline) < strtotime(date('Y-m-d'))) {
        $errors[] = "Deadline cannot be earlier than today.";
    }

    if (!in_array($status, ['open', 'closed'])) {
        $status = 'open';
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO scholarships 
            (provider_id, title, description, eligibility, required_documents, benefit, total_slots, location, deadline, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "isssssisss",
                $provider_id,
                $title,
                $description,
                $eligibility,
                $required_documents,
                $benefit,
                $total_slots,
                $location,
                $deadline,
                $status
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: scholarships.php?msg=created");
                exit;
            } else {
                $errors[] = "Failed to save scholarship.";
            }

            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Database error: unable to prepare query.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Scholarship | ScholarLink</title>
    <link rel="stylesheet" href="/scholarlink/pages/assets/css/provider-add-scholarship.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="scholarship-page">
    <div class="scholarship-container">

        <div class="page-header">
            <span class="page-badge">Scholarship Form</span>
            <h1>Add Scholarship</h1>
            <p>Create a scholarship listing through a guided step-by-step form.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Please fix the following:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="scholarship-form" id="scholarshipStepperForm">
            <div class="stepper-card">

                <div class="stepper-progress">
                    <span id="stepProgressBar"></span>
                </div>

                <div class="stepper-nav">
                    <div class="step-pill active" data-step-pill="1">
                        <span class="step-num">1</span>
                        <div>
                            <strong>Basic Info</strong>
                            <small>Title, benefit, location</small>
                        </div>
                    </div>

                    <div class="step-pill" data-step-pill="2">
                        <span class="step-num">2</span>
                        <div>
                            <strong>Requirements</strong>
                            <small>Eligibility, documents, slots</small>
                        </div>
                    </div>

                    <div class="step-pill" data-step-pill="3">
                        <span class="step-num">3</span>
                        <div>
                            <strong>Publish</strong>
                            <small>Deadline, status, review</small>
                        </div>
                    </div>
                </div>

                <!-- STEP 1 -->
                <div class="step-panel active" data-step="1">
                    <div class="form-section">
                        <h2>Basic Information</h2>
                        <p class="section-subtext">Enter the main scholarship details students will see first.</p>

                        <div class="form-group">
                            <label for="title">Scholarship Title</label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                value="<?= htmlspecialchars($title) ?>"
                                placeholder="e.g. UCC Academic Scholarship 2026"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="5"
                                placeholder="Write a short but clear description of the scholarship..."
                                required
                            ><?= htmlspecialchars($description) ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="benefit">Scholarship Benefit</label>
                                <input
                                    type="text"
                                    id="benefit"
                                    name="benefit"
                                    value="<?= htmlspecialchars($benefit) ?>"
                                    placeholder="e.g. Full tuition assistance"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="location">Location / Coverage</label>
                                <input
                                    type="text"
                                    id="location"
                                    name="location"
                                    value="<?= htmlspecialchars($location) ?>"
                                    placeholder="e.g. UCC Main Campus, Cebu City"
                                    required
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2 -->
                <div class="step-panel" data-step="2">
                    <div class="form-section no-border no-space">
                        <h2>Requirements</h2>
                        <p class="section-subtext">Define who can apply and what documents they need to submit.</p>

                        <div class="form-group">
                            <label for="eligibility">Eligibility Requirements</label>
                            <textarea
                                id="eligibility"
                                name="eligibility"
                                rows="5"
                                placeholder="e.g. Must be enrolled, no failing grades, minimum GWA of 1.75..."
                                required
                            ><?= htmlspecialchars($eligibility) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="required_documents">Required Documents</label>
                            <textarea
                                id="required_documents"
                                name="required_documents"
                                rows="5"
                                placeholder="e.g. COR, School ID, Latest Grades, Proof of Income..."
                                required
                            ><?= htmlspecialchars($required_documents) ?></textarea>
                        </div>

                        <div class="form-group form-group-sm">
                            <label for="total_slots">Total Slots</label>
                            <input
                                type="number"
                                id="total_slots"
                                name="total_slots"
                                min="1"
                                value="<?= htmlspecialchars($total_slots) ?>"
                                placeholder="e.g. 50"
                                required
                            >
                            <small class="field-note">This will be used for availability like 50/50, 49/50, and so on.</small>
                        </div>
                    </div>
                </div>

                <!-- STEP 3 -->
                <div class="step-panel" data-step="3">
                    <div class="form-section no-border no-space">
                        <h2>Deadline & Status</h2>
                        <p class="section-subtext">Finalize the scholarship and review everything before saving.</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="deadline">Application Deadline</label>
                                <input
                                    type="date"
                                    id="deadline"
                                    name="deadline"
                                    min="<?= date('Y-m-d') ?>"
                                    value="<?= htmlspecialchars($deadline) ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" required>
                                    <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option>
                                    <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option>
                                </select>
                            </div>
                        </div>

                        <div class="review-card">
                            <h3>Review Preview</h3>

                            <div class="review-grid">
                                <div class="review-item">
                                    <span>Title</span>
                                    <strong id="reviewTitle">—</strong>
                                </div>

                                <div class="review-item">
                                    <span>Benefit</span>
                                    <strong id="reviewBenefit">—</strong>
                                </div>

                                <div class="review-item">
                                    <span>Location</span>
                                    <strong id="reviewLocation">—</strong>
                                </div>

                                <div class="review-item">
                                    <span>Slots</span>
                                    <strong id="reviewSlots">—</strong>
                                </div>

                                <div class="review-item">
                                    <span>Deadline</span>
                                    <strong id="reviewDeadline">—</strong>
                                </div>

                                <div class="review-item">
                                    <span>Status</span>
                                    <strong id="reviewStatus">—</strong>
                                </div>
                            </div>

                            <div class="review-block">
                                <span>Description</span>
                                <p id="reviewDescription">—</p>
                            </div>

                            <div class="review-block">
                                <span>Eligibility</span>
                                <p id="reviewEligibility">—</p>
                            </div>

                            <div class="review-block">
                                <span>Required Documents</span>
                                <p id="reviewDocuments">—</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="stepper-actions">
                    <button type="button" class="btn btn-outline" id="prevBtn" style="display:none;">Back</button>
                    <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn" style="display:none;">Save Scholarship</button>
                    <a href="scholarships.php" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const panels = document.querySelectorAll('.step-panel');
    const pills = document.querySelectorAll('.step-pill');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const progressBar = document.getElementById('stepProgressBar');

    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const benefitInput = document.getElementById('benefit');
    const locationInput = document.getElementById('location');
    const eligibilityInput = document.getElementById('eligibility');
    const documentsInput = document.getElementById('required_documents');
    const slotsInput = document.getElementById('total_slots');
    const deadlineInput = document.getElementById('deadline');
    const statusInput = document.getElementById('status');

    let currentStep = 1;
    const totalSteps = 3;

    function safeValue(value) {
        return value && value.trim() !== '' ? value.trim() : '—';
    }

    function fillReview() {
        document.getElementById('reviewTitle').textContent = safeValue(titleInput.value);
        document.getElementById('reviewBenefit').textContent = safeValue(benefitInput.value);
        document.getElementById('reviewLocation').textContent = safeValue(locationInput.value);
        document.getElementById('reviewSlots').textContent = safeValue(slotsInput.value);
        document.getElementById('reviewDeadline').textContent = safeValue(deadlineInput.value);
        document.getElementById('reviewStatus').textContent = safeValue(statusInput.value);

        document.getElementById('reviewDescription').textContent = safeValue(descriptionInput.value);
        document.getElementById('reviewEligibility').textContent = safeValue(eligibilityInput.value);
        document.getElementById('reviewDocuments').textContent = safeValue(documentsInput.value);
    }

    function validateStep(step) {
        const currentPanel = document.querySelector('.step-panel[data-step="' + step + '"]');
        const requiredFields = currentPanel.querySelectorAll('[required]');

        for (let i = 0; i < requiredFields.length; i++) {
            if (!requiredFields[i].checkValidity()) {
                requiredFields[i].reportValidity();
                requiredFields[i].focus();
                return false;
            }
        }

        return true;
    }

    function updateStepper() {
        panels.forEach(panel => {
            panel.classList.remove('active');
            if (parseInt(panel.dataset.step) === currentStep) {
                panel.classList.add('active');
            }
        });

        pills.forEach(pill => {
            const pillStep = parseInt(pill.dataset.stepPill);
            pill.classList.remove('active', 'done');

            if (pillStep === currentStep) {
                pill.classList.add('active');
            } else if (pillStep < currentStep) {
                pill.classList.add('done');
            }
        });

        const progressWidth = ((currentStep - 1) / (totalSteps - 1)) * 100;
        progressBar.style.width = progressWidth + '%';

        prevBtn.style.display = currentStep === 1 ? 'none' : 'inline-flex';
        nextBtn.style.display = currentStep === totalSteps ? 'none' : 'inline-flex';
        submitBtn.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';

        if (currentStep === 3) {
            fillReview();
        }
    }

    nextBtn.addEventListener('click', function () {
        if (!validateStep(currentStep)) return;
        if (currentStep < totalSteps) {
            currentStep++;
            updateStepper();
        }
    });

    prevBtn.addEventListener('click', function () {
        if (currentStep > 1) {
            currentStep--;
            updateStepper();
        }
    });

    [titleInput, descriptionInput, benefitInput, locationInput, eligibilityInput, documentsInput, slotsInput, deadlineInput, statusInput]
        .forEach(field => {
            field.addEventListener('input', fillReview);
            field.addEventListener('change', fillReview);
        });

    updateStepper();
});
</script>

</body>
</html>