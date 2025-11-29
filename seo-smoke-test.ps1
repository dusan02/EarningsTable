# 🔍 SEO Smoke Test Script (PowerShell)
# Comprehensive SEO verification after deployment
# Usage: .\seo-smoke-test.ps1 [URL]
# Default URL: https://earningsstable.com

param(
    [string]$SiteUrl = "https://earningsstable.com"
)

$ErrorActionPreference = "Continue"

$Errors = 0
$Warnings = 0

function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

function Test-Status {
    param(
        [string]$Url,
        [int]$Expected,
        [string]$Name
    )
    
    try {
        $response = Invoke-WebRequest -Uri $Url -Method Head -UseBasicParsing -ErrorAction Stop
        $statusCode = $response.StatusCode
        
        if ($statusCode -eq $Expected) {
            Write-ColorOutput Green "✅ ${Name}: HTTP ${statusCode}"
            return $true
        } else {
            Write-ColorOutput Red "❌ ${Name}: HTTP ${statusCode} (expected ${Expected})"
            $script:Errors++
            return $false
        }
    } catch {
        Write-ColorOutput Red "❌ ${Name}: Error - $($_.Exception.Message)"
        $script:Errors++
        return $false
    }
}

function Test-Header {
    param(
        [string]$Url,
        [string]$Header,
        [string]$Expected,
        [string]$Name
    )
    
    try {
        $response = Invoke-WebRequest -Uri $Url -Method Head -UseBasicParsing -ErrorAction Stop
        $headerValue = $response.Headers[$Header]
        
        if ($headerValue -and $headerValue -match $Expected) {
            Write-ColorOutput Green "✅ ${Name}: $headerValue"
            return $true
        } else {
            Write-ColorOutput Red "❌ ${Name}: Expected '${Expected}' in ${Header}, got '$headerValue'"
            $script:Errors++
            return $false
        }
    } catch {
        Write-ColorOutput Red "❌ ${Name}: Error - $($_.Exception.Message)"
        $script:Errors++
        return $false
    }
}

function Test-Content {
    param(
        [string]$Url,
        [string]$Pattern,
        [string]$Name,
        [bool]$ShouldExist = $true
    )
    
    try {
        $content = Invoke-WebRequest -Uri $Url -UseBasicParsing -ErrorAction Stop | Select-Object -ExpandProperty Content
        
        if ($ShouldExist) {
            if ($content -match $Pattern) {
                Write-ColorOutput Green "✅ ${Name}: Found '${Pattern}'"
                return $true
            } else {
                Write-ColorOutput Red "❌ ${Name}: '${Pattern}' not found"
                $script:Errors++
                return $false
            }
        } else {
            if ($content -match $Pattern) {
                Write-ColorOutput Red "❌ ${Name}: '${Pattern}' should NOT exist but was found"
                $script:Errors++
                return $false
            } else {
                Write-ColorOutput Green "✅ ${Name}: '${Pattern}' correctly absent"
                return $true
            }
        }
    } catch {
        Write-ColorOutput Red "❌ ${Name}: Error - $($_.Exception.Message)"
        $script:Errors++
        return $false
    }
}

Write-ColorOutput Cyan "🔍 SEO Smoke Test for ${SiteUrl}"
Write-Output "=========================================="
Write-Output ""

Write-ColorOutput Cyan "1. Homepage Status & Headers"
Write-Output "-----------------------------------"
Test-Status -Url "${SiteUrl}/" -Expected 200 -Name "Homepage"
Test-Header -Url "${SiteUrl}/" -Header "X-Robots-Tag" -Expected "index, follow" -Name "X-Robots-Tag header"
Test-Header -Url "${SiteUrl}/" -Header "Content-Type" -Expected "text/html" -Name "Content-Type"
Write-Output ""

Write-ColorOutput Cyan "2. Robots.txt"
Write-Output "----------------"
Test-Status -Url "${SiteUrl}/robots.txt" -Expected 200 -Name "robots.txt"
Test-Header -Url "${SiteUrl}/robots.txt" -Header "Content-Type" -Expected "text/plain" -Name "robots.txt Content-Type"
Test-Content -Url "${SiteUrl}/robots.txt" -Pattern "User-agent" -Name "robots.txt content"
Test-Content -Url "${SiteUrl}/robots.txt" -Pattern "sitemap.xml" -Name "robots.txt sitemap reference"
Write-Output ""

Write-ColorOutput Cyan "3. Sitemap.xml"
Write-Output "-----------------"
Test-Status -Url "${SiteUrl}/sitemap.xml" -Expected 200 -Name "sitemap.xml"
Test-Header -Url "${SiteUrl}/sitemap.xml" -Header "Content-Type" -Expected "application/xml" -Name "sitemap.xml Content-Type"
Test-Content -Url "${SiteUrl}/sitemap.xml" -Pattern "urlset" -Name "sitemap.xml structure"
Test-Content -Url "${SiteUrl}/sitemap.xml" -Pattern "earningsstable.com" -Name "sitemap.xml URL"
Write-Output ""

Write-ColorOutput Cyan "4. Homepage Content Check"
Write-Output "---------------------------"
Test-Content -Url "${SiteUrl}/" -Pattern "earningsstable.com" -Name "Homepage contains new domain" -ShouldExist $true
Test-Content -Url "${SiteUrl}/" -Pattern "earnings-table.com" -Name "Homepage does NOT contain old domain" -ShouldExist $false
Test-Content -Url "${SiteUrl}/" -Pattern "canonical" -Name "Homepage has canonical link" -ShouldExist $true
Test-Content -Url "${SiteUrl}/" -Pattern "og:url" -Name "Homepage has Open Graph URL" -ShouldExist $true
Test-Content -Url "${SiteUrl}/" -Pattern "twitter:url" -Name "Homepage has Twitter URL" -ShouldExist $true
Write-Output ""

Write-ColorOutput Cyan "5. API Endpoints"
Write-Output "----------------"
Test-Status -Url "${SiteUrl}/api/health" -Expected 200 -Name "Health endpoint"
Test-Status -Url "${SiteUrl}/api/final-report" -Expected 200 -Name "Final Report endpoint"
Test-Header -Url "${SiteUrl}/api/final-report" -Header "X-Robots-Tag" -Expected "index, follow" -Name "API X-Robots-Tag"
Write-Output ""

Write-ColorOutput Cyan "6. Static Assets"
Write-Output "-----------------"
Test-Status -Url "${SiteUrl}/site.webmanifest" -Expected 200 -Name "Web Manifest"
Test-Status -Url "${SiteUrl}/favicon.svg" -Expected 200 -Name "Favicon SVG"
Write-Output ""

Write-ColorOutput Cyan "7. Response Time Check"
Write-Output "----------------------"
try {
    $homepageTime = Measure-Command { Invoke-WebRequest -Uri "${SiteUrl}/" -UseBasicParsing -ErrorAction Stop }
    $homepageMs = [math]::Round($homepageTime.TotalMilliseconds)
    
    if ($homepageMs -lt 3000) {
        Write-ColorOutput Green "✅ Homepage response time: ${homepageMs}ms (< 3s)"
    } else {
        Write-ColorOutput Yellow "⚠️  Homepage response time: ${homepageMs}ms (>= 3s)"
        $script:Warnings++
    }
    
    $apiTime = Measure-Command { Invoke-WebRequest -Uri "${SiteUrl}/api/final-report" -UseBasicParsing -ErrorAction Stop }
    $apiMs = [math]::Round($apiTime.TotalMilliseconds)
    
    if ($apiMs -lt 3000) {
        Write-ColorOutput Green "✅ API response time: ${apiMs}ms (< 3s)"
    } else {
        Write-ColorOutput Yellow "⚠️  API response time: ${apiMs}ms (>= 3s)"
        $script:Warnings++
    }
} catch {
    Write-ColorOutput Yellow "⚠️  Could not measure response time: $($_.Exception.Message)"
    $script:Warnings++
}
Write-Output ""

Write-Output "=========================================="
if ($Errors -eq 0 -and $Warnings -eq 0) {
    Write-ColorOutput Green "✅ All SEO checks passed!"
    exit 0
} elseif ($Errors -eq 0) {
    Write-ColorOutput Yellow "⚠️  SEO checks passed with ${Warnings} warning(s)"
    exit 0
} else {
    Write-ColorOutput Red "❌ SEO checks failed: ${Errors} error(s), ${Warnings} warning(s)"
    exit 1
}

