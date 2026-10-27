<?php

namespace App\Http\Controllers;

use App\Services\SupplierService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierService $service
    ) {}

    // Tampilkan daftar pemasok untuk halaman pengelolaan supplier.
    public function index()
    {
        $suppliers = $this->service->getAllSuppliers();
        return view('pages.suppliers.index', compact('suppliers'));
    }

    // Validasi lalu simpan data pemasok baru.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $this->service->createSupplier($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil ditambahkan!');
    }

    // Ambil data pemasok untuk formulir edit.
    public function edit($id)
    {
        $supplier = $this->service->getSupplierById((int) $id);
        return response()->json($supplier);
    }

    // Simpan perubahan data pemasok yang telah divalidasi.
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $this->service->updateSupplier((int) $id, $validated);

        return redirect()->route('suppliers.index')->with('success', 'Data Supplier berhasil diperbarui!');
    }

    // Hapus pemasok berdasarkan ID.
    public function destroy($id)
    {
        $this->service->deleteSupplier((int) $id);
        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil dihapus!');
    }
}
