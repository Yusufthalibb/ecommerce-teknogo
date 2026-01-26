<?php
// app/Http/Controllers/Customer/WishlistController.php


namespace App\Http\Controllers\Customer;
use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Display wishlist
     */
    public function index()
    {
        $wishlists = Wishlist::with('product.category')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('customer.wishlist.index', compact('wishlists'));
    }

    /**
     * Toggle wishlist (add/remove)
     */
    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $wishlist = Wishlist::where('user_id', Auth::id())
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($wishlist) {
            // Remove from wishlist
            $wishlist->delete();
            $message = 'Produk dihapus dari wishlist';
            $inWishlist = false;
        } else {
            // Add to wishlist
            Wishlist::create([
                'user_id' => Auth::id(),
                'product_id' => $validated['product_id'],
            ]);
            $message = 'Produk ditambahkan ke wishlist';
            $inWishlist = true;
        }

        // Return JSON untuk AJAX
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'in_wishlist' => $inWishlist,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Remove from wishlist
     */
    public function destroy(Wishlist $wishlist)
    {
        // Authorize
        if ($wishlist->user_id !== Auth::id()) {
            abort(403);
        }

        $wishlist->delete();

        return back()->with('success', 'Produk dihapus dari wishlist');
    }
}