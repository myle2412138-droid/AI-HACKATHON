/**
 * Apply Modal Component
 * Modal để apply vào research project với cover letter
 */

import { megallm } from '../megallm-client.js';

class ApplyModal {
    constructor() {
        this.modal = null;
        this.currentProject = null;
        this.init();
    }
    
    init() {
        // Create modal HTML
        const modalHTML = `
        <div class="modal-overlay" id="applyModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h2>✈️ Apply to Research Project</h2>
                    <button class="modal-close" onclick="applyModal.close()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="project-preview" id="projectPreview">
                        <!-- Project info will be inserted here -->
                    </div>
                    
                    <form id="applyForm">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-envelope"></i>
                                Thư Xin Tham Gia <span class="required">*</span>
                            </label>
                            <textarea 
                                id="coverLetter"
                                name="coverLetter" 
                                rows="10" 
                                required
                                placeholder="Giới thiệu bản thân, lý do muốn tham gia dự án này, kinh nghiệm và kỹ năng liên quan..."
                            ></textarea>
                            <div class="ai-suggest" style="margin-top: 1rem;">
                                <button type="button" class="btn-outline btn-sm" onclick="applyModal.aiSuggest()">
                                    <i class="fas fa-magic"></i> AI Gợi Ý Cover Letter
                                </button>
                                <span class="loading-text" id="aiSuggestLoading" style="display: none;">
                                    AI đang soạn thảo...
                                </span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-star"></i>
                                Kinh Nghiệm Liên Quan
                            </label>
                            <textarea 
                                id="relevantExp"
                                name="relevantExperience" 
                                rows="5"
                                placeholder="Projects, courses, skills liên quan đến đề tài này (không bắt buộc)..."
                            ></textarea>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn-outline" onclick="applyModal.close()">
                                Hủy
                            </button>
                            <button type="submit" class="btn-primary" id="submitBtn">
                                <i class="fas fa-paper-plane"></i>
                                Gửi Đơn Apply
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        `;
        
        // Insert vào body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modal = document.getElementById('applyModal');
        
        // Setup form submit
        document.getElementById('applyForm').addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Close khi click outside
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });
    }
    
    /**
     * Open modal với project info
     */
    open(project) {
        this.currentProject = project;
        
        // Fill project preview
        document.getElementById('projectPreview').innerHTML = `
            <h3>${project.title}</h3>
            <p>
                <strong>${project.lecturer_name}</strong> - ${project.lecturer_university || 'Victoria AI'}
            </p>
            <div class="card-meta" style="margin-top: 1rem;">
                <span>👥 ${project.current_students || 0}/${project.max_students || 3} students</span>
                <span>⏰ ${project.duration || '6 tháng'}</span>
            </div>
        `;
        
        // Clear form
        document.getElementById('coverLetter').value = '';
        document.getElementById('relevantExp').value = '';
        
        // Show modal
        this.modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    
    /**
     * Close modal
     */
    close() {
        this.modal.classList.remove('show');
        document.body.style.overflow = '';
        this.currentProject = null;
    }
    
    /**
     * AI suggest cover letter
     */
    async aiSuggest() {
        const loadingEl = document.getElementById('aiSuggestLoading');
        const textarea = document.getElementById('coverLetter');
        
        try {
            loadingEl.style.display = 'inline';
            
            // Get user profile từ localStorage hoặc API
            const userProfile = await this.getUserProfile();
            
            const prompt = `Write a professional cover letter in Vietnamese for:
Student: ${userProfile.name}, ${userProfile.major} - ${userProfile.university}
Project: ${this.currentProject.title}
Requirements: ${this.currentProject.requirements || 'N/A'}

Make it personal, enthusiastic, and professional. 150-200 words.`;
            
            const response = await megallm.chat([
                { role: 'system', content: 'You are a career advisor writing cover letters.' },
                { role: 'user', content: prompt }
            ], 'gpt-5');
            
            const suggestion = response.choices[0].message.content;
            textarea.value = suggestion;
            
            loadingEl.style.display = 'none';
            
            // Show success
            this.showToast('AI đã tạo cover letter! Bạn có thể chỉnh sửa.', 'success');
            
        } catch (error) {
            console.error('AI suggest error:', error);
            loadingEl.style.display = 'none';
            this.showToast('Lỗi: ' + error.message, 'error');
        }
    }
    
    /**
     * Get user profile
     */
    async getUserProfile() {
        // Mock data - trong thực tế lấy từ API
        return {
            name: 'Nguyễn Văn A',
            major: 'Khoa học máy tính',
            university: 'Đại học Bách Khoa TP.HCM'
        };
    }
    
    /**
     * Handle form submit
     */
    async handleSubmit(e) {
        e.preventDefault();
        
        const coverLetter = document.getElementById('coverLetter').value;
        const relevantExp = document.getElementById('relevantExp').value;
        const submitBtn = document.getElementById('submitBtn');
        
        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
            
            // Call API
            const response = await fetch('https://bkuteam.site/php/api/applications/apply.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    project_id: this.currentProject.id,
                    cover_letter: coverLetter,
                    relevant_experience: relevantExp
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('Đã gửi đơn apply thành công! 🎉', 'success');
                this.close();
                
                // Reload page hoặc update UI
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                this.showToast('Lỗi: ' + result.message, 'error');
            }
            
        } catch (error) {
            console.error('Apply error:', error);
            this.showToast('Lỗi kết nối: ' + error.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi Đơn Apply';
        }
    }
    
    /**
     * Show toast notification
     */
    showToast(message, type) {
        // Reuse toast from dashboard hoặc tạo mới
        let toast = document.getElementById('toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast';
            toast.className = 'toast';
            document.body.appendChild(toast);
        }
        
        toast.textContent = message;
        toast.className = `toast toast-${type} show`;
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
}

// Export singleton
const applyModal = new ApplyModal();
export default applyModal;

// Make available globally
window.applyModal = applyModal;

console.log('✈️ Apply Modal loaded');

