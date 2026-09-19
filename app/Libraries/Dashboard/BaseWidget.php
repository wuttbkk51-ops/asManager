<?php

namespace App\Libraries\Dashboard;

abstract class BaseWidget implements WidgetInterface
{
    protected string $id = '';
    protected string $title = '';
    protected string $description = '';
    protected string $category = 'general';
    protected string $requiredPermission = 'dashboard';
    protected string $defaultCol = 'col-lg-3 col-sm-6';
    protected string $icon = 'bi bi-app-indicator';

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getRequiredPermission(): string
    {
        return $this->requiredPermission;
    }

    public function getDefaultCol(): string
    {
        return $this->defaultCol;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * ตรวจสอบว่าผู้ใช้ปัจจุบันมีสิทธิ์ในการเข้าถึงวิดเจ็ตนี้หรือไม่
     */
    public function authorize(): bool
    {
        if (function_exists('can')) {
            return can($this->getRequiredPermission());
        }
        return true;
    }

    /**
     * Helper สำหรับสร้าง AdminLTE 4 Small Box
     */
    protected function buildSmallBox(string $bgClass, string $value, string $label, string $iconClass, ?string $footerText = null, ?string $footerUrl = null): string
    {
        $footerHtml = '';
        if ($footerText) {
            $url = $footerUrl ?: '#';
            $footerHtml = <<<HTML
            <a href="{$url}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                {$footerText} <i class="bi bi-arrow-right-circle ms-1"></i>
            </a>
HTML;
        }

        return <<<HTML
        <div class="small-box {$bgClass} shadow-sm rounded-3 mb-0">
          <div class="inner">
            <h3 class="fw-bold">{$value}</h3>
            <p class="mb-0">{$label}</p>
          </div>
          <div class="small-box-icon">
            <i class="{$iconClass}"></i>
          </div>
          {$footerHtml}
        </div>
HTML;
    }

    /**
     * Helper สำหรับสร้าง AdminLTE 4 Card Header
     */
    protected function buildCard(string $outlineClass, string $title, string $iconClass, string $bodyHtml, ?string $badgeText = null, string $badgeClass = 'text-bg-primary'): string
    {
        $badgeHtml = $badgeText ? "<span class=\"badge {$badgeClass} rounded-pill\">{$badgeText}</span>" : '';

        return <<<HTML
        <div class="card {$outlineClass} card-outline shadow-sm h-100 mb-0">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold mb-0">
              <i class="{$iconClass} me-2"></i>{$title}
            </h5>
            {$badgeHtml}
          </div>
          <div class="card-body p-0">
            {$bodyHtml}
          </div>
        </div>
HTML;
    }
}
