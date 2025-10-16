"use client"

import { useState, useEffect, useMemo } from "react"
import { ArrowUpDown, ArrowUp, ArrowDown, Search, Moon, Sun } from "lucide-react"

type SortField = "symbol" | "marketCap" | "price" | "epsActual" | "revActual"
type SortDirection = "asc" | "desc"

interface FinalReportData {
  symbol: string
  name: string | null
  size: string | null
  marketCap: bigint | null
  marketCapDiff: bigint | null
  price: number | null
  change: number | null
  epsActual: number | null
  epsEst: number | null
  epsSurp: number | null
  revActual: bigint | null
  revEst: bigint | null
  revSurp: number | null
}

export default function EarningsDashboard() {
  const [data, setData] = useState<FinalReportData[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [searchQuery, setSearchQuery] = useState("")
  const [sortField, setSortField] = useState<SortField>("marketCap")
  const [sortDirection, setSortDirection] = useState<SortDirection>("desc")
  const [theme, setTheme] = useState<"light" | "dark">("light")

  // Theme management
  useEffect(() => {
    const savedTheme = localStorage.getItem("theme") as "light" | "dark" | null
    const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches
    const initialTheme = savedTheme || (prefersDark ? "dark" : "light")

    setTheme(initialTheme)
    document.documentElement.classList.toggle("dark", initialTheme === "dark")
  }, [])

  const toggleTheme = () => {
    const newTheme = theme === "light" ? "dark" : "light"
    setTheme(newTheme)
    localStorage.setItem("theme", newTheme)
    document.documentElement.classList.toggle("dark", newTheme === "dark")
  }

  // Data fetching
  const fetchData = async () => {
    try {
      setLoading(true)
      setError(null)
      const response = await fetch('/api/final-report')
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      const result = await response.json()
      
      if (result.success) {
        setData(result.data)
      } else {
        throw new Error(result.message || 'Failed to fetch data')
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch data')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    fetchData()
    
    // Auto-refresh every 5 minutes
    const interval = setInterval(fetchData, 5 * 60 * 1000)
    return () => clearInterval(interval)
  }, [])

  // Sorting and filtering
  const filteredAndSortedData = useMemo(() => {
    let filtered = data

    if (searchQuery) {
      filtered = filtered.filter(
        (company) =>
          company.symbol.toLowerCase().includes(searchQuery.toLowerCase()) ||
          (company.name && company.name.toLowerCase().includes(searchQuery.toLowerCase()))
      )
    }

    return filtered.sort((a, b) => {
      const aValue = a[sortField]
      const bValue = b[sortField]
      
      if (aValue === null || aValue === undefined) return 1
      if (bValue === null || bValue === undefined) return -1
      
      let aNum = typeof aValue === 'bigint' ? Number(aValue) : aValue
      let bNum = typeof bValue === 'bigint' ? Number(bValue) : bValue
      
      if (typeof aNum === 'string' && typeof bNum === 'string') {
        return sortDirection === "asc" 
          ? aNum.localeCompare(bNum)
          : bNum.localeCompare(aNum)
      }
      
      const multiplier = sortDirection === "asc" ? 1 : -1
      return (aNum > bNum ? 1 : -1) * multiplier
    })
  }, [data, searchQuery, sortField, sortDirection])

  const handleSort = (field: SortField) => {
    if (sortField === field) {
      setSortDirection(sortDirection === "asc" ? "desc" : "asc")
    } else {
      setSortField(field)
      setSortDirection("desc")
    }
  }

  // Utility functions
  const formatMarketCap = (value: bigint | null) => {
    if (!value) return 'N/A'
    const num = Number(value)
    if (num >= 1e12) return `$${(num / 1e12).toFixed(2)}T`
    if (num >= 1e9) return `$${(num / 1e9).toFixed(2)}B`
    if (num >= 1e6) return `$${(num / 1e6).toFixed(2)}M`
    return `$${num.toFixed(0)}`
  }

  const formatRevenue = (value: bigint | null) => {
    if (!value) return 'N/A'
    const num = Number(value)
    if (num >= 1e9) return `$${(num / 1e9).toFixed(2)}B`
    if (num >= 1e6) return `$${(num / 1e6).toFixed(2)}M`
    return `$${num.toFixed(0)}`
  }

  const getSizeBadgeColor = (size: string | null) => {
    switch (size?.toLowerCase()) {
      case 'mega': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
      case 'large': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
      case 'mid': return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
      case 'small': return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
      default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
    }
  }

  const getChangeColor = (value: number | null) => {
    if (!value) return 'text-gray-600 dark:text-gray-400'
    if (value > 0) return 'text-green-600 dark:text-green-400'
    if (value < 0) return 'text-red-600 dark:text-red-400'
    return 'text-gray-600 dark:text-gray-400'
  }

  const SortIcon = ({ field }: { field: SortField }) => {
    if (sortField !== field) {
      return <ArrowUpDown className="h-4 w-4" />
    }
    return sortDirection === "asc" ? <ArrowUp className="h-4 w-4" /> : <ArrowDown className="h-4 w-4" />
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600 dark:text-gray-400">Loading earnings data...</p>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
        <div className="text-center">
          <div className="text-red-500 text-6xl mb-4">⚠️</div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            Error Loading Data
          </h2>
          <p className="text-gray-600 dark:text-gray-400 mb-4">{error}</p>
          <button
            onClick={fetchData}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Retry
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      {/* Header */}
      <header className="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                Earnings Dashboard
              </h1>
              <p className="mt-2 text-gray-600 dark:text-gray-400">
                Company earnings and financial data
              </p>
            </div>
            
            <div className="mt-4 sm:mt-0 flex items-center space-x-4">
              {/* Theme Toggle */}
              <button
                onClick={toggleTheme}
                className="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
              >
                {theme === 'light' ? <Moon className="h-5 w-5" /> : <Sun className="h-5 w-5" />}
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Search */}
        <div className="mb-6">
          <div className="relative max-w-md">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
            <input
              type="text"
              placeholder="Search companies..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </div>

        {/* Table */}
        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead className="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    <button
                      onClick={() => handleSort('symbol')}
                      className="flex items-center space-x-1 hover:text-blue-600 transition-colors"
                    >
                      <span>Company</span>
                      <SortIcon field="symbol" />
                    </button>
                  </th>
                  
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Size
                  </th>
                  
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    <button
                      onClick={() => handleSort('marketCap')}
                      className="flex items-center space-x-1 hover:text-blue-600 transition-colors"
                    >
                      <span>Market Cap</span>
                      <SortIcon field="marketCap" />
                    </button>
                  </th>
                  
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    <button
                      onClick={() => handleSort('price')}
                      className="flex items-center space-x-1 hover:text-blue-600 transition-colors"
                    >
                      <span>Price</span>
                      <SortIcon field="price" />
                    </button>
                  </th>
                  
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    <button
                      onClick={() => handleSort('epsActual')}
                      className="flex items-center space-x-1 hover:text-blue-600 transition-colors"
                    >
                      <span>EPS</span>
                      <SortIcon field="epsActual" />
                    </button>
                  </th>
                  
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    <button
                      onClick={() => handleSort('revActual')}
                      className="flex items-center space-x-1 hover:text-blue-600 transition-colors"
                    >
                      <span>Revenue</span>
                      <SortIcon field="revActual" />
                    </button>
                  </th>
                </tr>
              </thead>
              
              <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                {filteredAndSortedData.map((item, index) => (
                  <tr 
                    key={item.symbol}
                    className="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                  >
                    {/* Company */}
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div className="text-sm font-bold text-gray-900 dark:text-white">
                          {item.symbol}
                        </div>
                        <div className="text-sm text-gray-500 dark:text-gray-400">
                          {item.name || 'N/A'}
                        </div>
                      </div>
                    </td>
                    
                    {/* Size */}
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getSizeBadgeColor(item.size)}`}>
                        {item.size || 'Unknown'}
                      </span>
                    </td>
                    
                    {/* Market Cap */}
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div className="text-sm font-medium text-gray-900 dark:text-white">
                          {formatMarketCap(item.marketCap)}
                        </div>
                        <div className={`text-sm ${getChangeColor(item.change)}`}>
                          {item.change ? `${item.change > 0 ? '+' : ''}${item.change.toFixed(2)}%` : 'N/A'}
                        </div>
                      </div>
                    </td>
                    
                    {/* Price */}
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div className="text-sm font-medium text-gray-900 dark:text-white">
                          {item.price ? `$${item.price.toFixed(2)}` : 'N/A'}
                        </div>
                        <div className={`text-sm ${getChangeColor(item.change)}`}>
                          {item.change ? `${item.change > 0 ? '+' : ''}${item.change.toFixed(2)}%` : 'N/A'}
                        </div>
                      </div>
                    </td>
                    
                    {/* EPS */}
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="space-y-1 text-sm">
                        <div className="text-gray-900 dark:text-white font-medium">
                          {item.epsActual ? `$${item.epsActual.toFixed(2)}` : 'N/A'}
                        </div>
                        <div className="text-gray-600 dark:text-gray-400">
                          {item.epsEst ? `$${item.epsEst.toFixed(2)}` : 'N/A'}
                        </div>
                        <div className={`${getChangeColor(item.epsSurp)} font-medium`}>
                          {item.epsSurp ? `${item.epsSurp > 0 ? '+' : ''}${item.epsSurp.toFixed(2)}%` : 'N/A'}
                        </div>
                      </div>
                    </td>
                    
                    {/* Revenue */}
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="space-y-1 text-sm">
                        <div className="text-gray-900 dark:text-white font-medium">
                          {formatRevenue(item.revActual)}
                        </div>
                        <div className="text-gray-600 dark:text-gray-400">
                          {formatRevenue(item.revEst)}
                        </div>
                        <div className={`${getChangeColor(item.revSurp)} font-medium`}>
                          {item.revSurp ? `${item.revSurp > 0 ? '+' : ''}${item.revSurp.toFixed(2)}%` : 'N/A'}
                        </div>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          
          {filteredAndSortedData.length === 0 && (
            <div className="text-center py-12">
              <div className="text-gray-400 text-lg">No companies found</div>
              <div className="text-gray-500 text-sm mt-2">
                Try adjusting your search terms
              </div>
            </div>
          )}
        </div>
        
        {/* Footer */}
        <div className="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
          Showing {filteredAndSortedData.length} of {data.length} companies
        </div>
      </div>
    </div>
  )
}
